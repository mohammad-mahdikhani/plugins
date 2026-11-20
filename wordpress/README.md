# WordPress (nahal.website)

Cursor plugin that connects agents to [nahal.website](https://nahal.website) through [Easy MCP AI](https://wordpress.org/plugins/easy-mcp-ai/).

The server is `https://nahal.website/wp-json/easy-mcp-ai/v1/mcp` (Easy MCP AI 2.5.0).

## Install

1. Open **Cursor Settings → Plugins**.
2. Add this repository as a marketplace, then install **WordPress (nahal.website)**.
3. Open **Plugins → Configure** and set **WordPress API token** to the `wpmcp_` token from **Easy MCP AI → Connections → API tokens** on nahal.website.

The token is not stored in this repo. Cursor substitutes `${WORDPRESS_API_TOKEN}` into the Authorization header when the plugin connects.

## MCP

```json
{
  "mcpServers": {
    "wordpress": {
      "type": "http",
      "url": "https://nahal.website/wp-json/easy-mcp-ai/v1/mcp",
      "headers": {
        "Authorization": "Bearer ${WORDPRESS_API_TOKEN}"
      }
    }
  }
}
```

## What the agent can do

Posts, pages, media, comments, menus, blocks, templates, and custom post types. Rank Math and WP Rocket are enabled on this site. Tools that are switched off in WordPress do not appear; the agent calls `wp_list_disabled_tools` before treating a missing tool as impossible.

## Token

Create or rotate the token in WordPress under **Easy MCP AI → Connections → API tokens**. It is shown once and starts with `wpmcp_`. Revoke it there if it is ever exposed. Do not commit it.

## License

MIT
