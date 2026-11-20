<?php
/**
 * FAQ Gutenberg block — visible on the page (Google requires visible content).
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Blocks;

final class Register
{
    public function register(): void
    {
        add_action('init', [$this, 'blocks']);
    }

    public function blocks(): void
    {
        if (! function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'schema-nova-editor',
            SCHEMA_NOVA_URL . 'assets/editor.js',
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            SCHEMA_NOVA_VERSION,
            true
        );

        register_block_type('schema-nova/faq', [
            'editor_script'   => 'schema-nova-editor',
            'render_callback' => [$this, 'renderFaq'],
            'attributes'      => [
                'questions' => [
                    'type'    => 'array',
                    'default' => [],
                ],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function renderFaq(array $attrs): string
    {
        $questions = $attrs['questions'] ?? [];
        if (! is_array($questions) || $questions === []) {
            return '';
        }

        $html = '<div class="schema-nova-faq">';
        foreach ($questions as $row) {
            $q = esc_html((string) ($row['question'] ?? ''));
            $a = wp_kses_post((string) ($row['answer'] ?? ''));
            if ($q === '' || $a === '') {
                continue;
            }
            $html .= '<div class="schema-nova-faq__item">';
            $html .= '<h3 class="schema-nova-faq__q">' . $q . '</h3>';
            $html .= '<div class="schema-nova-faq__a">' . $a . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
