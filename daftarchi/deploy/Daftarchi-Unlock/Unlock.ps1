# Unlocks an already installed Daftarchi (trial wall). ASCII log + Persian message box.
$ErrorActionPreference = "Continue"
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$sqlite = Join-Path $here "sqlite3.exe"
$sqlFile = Join-Path $here "unlock.sql"
$log = New-Object System.Collections.Generic.List[string]
function Add-Log([string]$line) {
  $script:log.Add($line)
  Write-Host $line
}

Add-Log ("start " + (Get-Date -Format "yyyy-MM-dd HH:mm:ss"))

function Stop-Daftarchi {
  Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object {
    $_.Name -match '^(pythonw|python|Daftarchi)\.exe$' -and (
      ($_.CommandLine -match 'daftarchi|app\.py') -or ($_.ExecutablePath -match 'Daftarchi|daftarchi')
    )
  } | ForEach-Object {
    Add-Log ("stop pid " + $_.ProcessId + " " + $_.Name)
    Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
  }
  Start-Sleep -Seconds 1
}

function Get-InstallHints {
  $faName = -join @(0x062F, 0x0641, 0x062A, 0x0631, 0x0686, 0x06CC | ForEach-Object { [char]$_ })
  $nameRe = "Daftarchi|daftarchi|" + [regex]::Escape($faName)
  $hints = New-Object System.Collections.Generic.List[string]
  if ($args.Count -gt 0 -and $args[0]) { [void]$hints.Add([string]$args[0]) }
  $regRoots = @(
    "HKCU:\Software\Microsoft\Windows\CurrentVersion\Uninstall\*",
    "HKLM:\Software\Microsoft\Windows\CurrentVersion\Uninstall\*",
    "HKLM:\Software\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*"
  )
  foreach ($root in $regRoots) {
    Get-ItemProperty $root -ErrorAction SilentlyContinue | Where-Object {
      $_.DisplayName -match $nameRe
    } | ForEach-Object {
      if ($_.InstallLocation) { [void]$hints.Add([string]$_.InstallLocation) }
      if ($_.DisplayIcon) { [void]$hints.Add([string]$_.DisplayIcon) }
    }
  }
  $menus = @(
    (Join-Path $env:ProgramData "Microsoft\Windows\Start Menu\Programs"),
    (Join-Path $env:APPDATA "Microsoft\Windows\Start Menu\Programs"),
    (Join-Path $env:USERPROFILE "Desktop")
  )
  $shell = $null
  try { $shell = New-Object -ComObject WScript.Shell } catch { $shell = $null }
  foreach ($menu in $menus) {
    if (-not (Test-Path $menu)) { continue }
    Get-ChildItem $menu -Filter *.lnk -Recurse -ErrorAction SilentlyContinue | ForEach-Object {
      if (-not $shell) { return }
      if ($_.Name -notmatch $nameRe) { return }
      try {
        $link = $shell.CreateShortcut($_.FullName)
        if ($link.TargetPath) { [void]$hints.Add([string]$link.TargetPath) }
        if ($link.WorkingDirectory) { [void]$hints.Add([string]$link.WorkingDirectory) }
        if ($link.Arguments -match '[A-Za-z]:\\[^"\s]+') { [void]$hints.Add($Matches[0]) }
      } catch {}
    }
  }
  $fixed = @(
    (Join-Path $env:LOCALAPPDATA "Daftarchi"),
    (Join-Path $env:APPDATA "Daftarchi"),
    "C:\Daftarchi", "D:\Daftarchi", "E:\Daftarchi",
    "C:\Program Files\Daftarchi", "C:\Program Files (x86)\Daftarchi"
  )
  foreach ($item in $fixed) { [void]$hints.Add($item) }
  return $hints
}

function Find-Files([string]$name, [string[]]$roots, [int]$depth) {
  $found = @()
  foreach ($root in $roots) {
    if (-not $root -or -not (Test-Path -LiteralPath $root)) { continue }
    Add-Log ("scan " + $root)
    $found += Get-ChildItem -LiteralPath $root -Filter $name -Recurse -Depth $depth -File -ErrorAction SilentlyContinue
  }
  return $found | Sort-Object FullName -Unique
}

function Patch-AppPy([string]$path) {
  $text = [System.IO.File]::ReadAllText($path)
  if ($text -notmatch 'def ensure_access') { return $false }
  if ($text -match 'STUDIOPATCH') {
    Add-Log ("already patched " + $path)
    return $true
  }
  $needle = [regex]'def ensure_access\(conn\)(?:\s*->\s*dict)?\s*:'
  if (-not $needle.IsMatch($text)) {
    Add-Log ("ensure_access shape unknown " + $path)
    return $false
  }
  $insert = "def ensure_access(conn):`r`n    return {`"mode`": `"paid`", `"active`": True, `"title`": `"STUDIOPATCH`", `"days_left`": 99999, `"ends_at`": `"2076-10-29T00:00:00+03:30`", `"ends_jalali`": `"`"}`r`n"
  $updated = $needle.Replace($text, $insert, 1)
  $backup = $path + ".bak-trial"
  if (-not (Test-Path -LiteralPath $backup)) { Copy-Item -LiteralPath $path -Destination $backup }
  [System.IO.File]::WriteAllText($path, $updated)
  Add-Log ("patched app.py " + $path)
  return $true
}

function Unlock-Db([string]$path) {
  if (-not (Test-Path -LiteralPath $sqlite)) {
    Add-Log "sqlite3.exe missing"
    return $false
  }
  $raw = [System.IO.File]::ReadAllText($sqlFile)
  $psi = New-Object System.Diagnostics.ProcessStartInfo
  $psi.FileName = $sqlite
  $psi.Arguments = '"' + $path + '"'
  $psi.RedirectStandardInput = $true
  $psi.RedirectStandardError = $true
  $psi.RedirectStandardOutput = $true
  $psi.UseShellExecute = $false
  $psi.CreateNoWindow = $true
  $proc = New-Object System.Diagnostics.Process
  $proc.StartInfo = $psi
  [void]$proc.Start()
  $proc.StandardInput.Write($raw)
  $proc.StandardInput.Close()
  $err = $proc.StandardError.ReadToEnd()
  $proc.WaitForExit()
  if ($proc.ExitCode -ne 0) {
    Add-Log ("db error " + $path + " " + $err.Trim())
    return $false
  }
  Add-Log ("db unlocked " + $path)
  return $true
}

Stop-Daftarchi

$hintRoots = @()
if ($args.Count -gt 0 -and $args[0]) {
  $dropped = $args[0]
  if (Test-Path -LiteralPath $dropped) {
    $item = Get-Item -LiteralPath $dropped
    if ($item.PSIsContainer) { $hintRoots += $item.FullName }
    else { $hintRoots += $item.Directory.FullName }
  }
}
$hintRoots += Get-InstallHints

$searchRoots = @()
$searchRoots += $hintRoots
$searchRoots += @(
  (Join-Path $env:LOCALAPPDATA "Daftarchi")
  (Join-Path $env:LOCALAPPDATA "Programs")
  (Join-Path $env:APPDATA "Daftarchi")
  (Join-Path $env:USERPROFILE "Desktop")
  (Join-Path $env:USERPROFILE "Downloads")
  (Join-Path $env:USERPROFILE "Documents")
  "C:\Daftarchi"
  "D:\Daftarchi"
  "E:\Daftarchi"
  "C:\Program Files\Daftarchi"
  "C:\Program Files (x86)\Daftarchi"
)
foreach ($drive in @("C:\", "D:\", "E:\")) {
  if (-not (Test-Path $drive)) { continue }
  Get-ChildItem $drive -Directory -ErrorAction SilentlyContinue | Where-Object {
    $_.Name -match 'Daftarchi|daftarchi'
  } | ForEach-Object { $searchRoots += $_.FullName }
}
if (Test-Path $env:USERPROFILE) {
  Get-ChildItem $env:USERPROFILE -Directory -ErrorAction SilentlyContinue | Where-Object {
    $_.Name -notin @("AppData", "node_modules", ".git") -and $_.Name -match 'Daftarchi|daftarchi|Desktop|Downloads|Documents'
  } | ForEach-Object { $searchRoots += $_.FullName }
}
$searchRoots = $searchRoots | Where-Object { $_ } | Select-Object -Unique

$pyFiles = Find-Files "app.py" $searchRoots 5
$dbs = Find-Files "daftarchi.db" $searchRoots 6

$patched = 0
foreach ($file in $pyFiles) {
  if (Patch-AppPy $file.FullName) { $patched++ }
}
$opened = 0
foreach ($file in $dbs) {
  if (Unlock-Db $file.FullName) { $opened++ }
}

Add-Log ("patched=" + $patched + " databases=" + $opened)
$logText = $log -join "`r`n"
$logPath = Join-Path $here "unlock-log.txt"
[System.IO.File]::WriteAllText($logPath, $logText)
$desk = Join-Path $env:USERPROFILE "Desktop\daftarchi-unlock-log.txt"
try { [System.IO.File]::WriteAllText($desk, $logText) } catch {}

if ($patched -gt 0 -or $opened -gt 0) {
  $msg = "Anjam shod. Daftarchi ra bebandid va dobare baz konid.`napp.py: $patched`ndatabase: $opened`n$logPath"
  Add-Type -AssemblyName System.Windows.Forms
  [System.Windows.Forms.MessageBox]::Show($msg, "Daftarchi") | Out-Null
  exit 0
}
$msg = "Peyda nashod.`nMianbor Daftarchi ra roye Unlock.bat bekeshid.`n$logPath"
Add-Type -AssemblyName System.Windows.Forms
[System.Windows.Forms.MessageBox]::Show($msg, "Daftarchi") | Out-Null
exit 1
