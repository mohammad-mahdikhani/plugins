<?php
/**
 * Per-post schema + SERP title editor.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Admin;

final class Metabox
{
    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'box']);
        add_action('save_post', [$this, 'save'], 10, 2);
    }

    public function box(): void
    {
        foreach (get_post_types(['public' => true]) as $type) {
            if ($type === 'attachment') {
                continue;
            }
            add_meta_box(
                'schema-nova',
                sn__('هوش‌اسکیما — Schema & SERP'),
                [$this, 'render'],
                $type,
                'normal',
                'high'
            );
        }
    }

    public function render(\WP_Post $post): void
    {
        wp_nonce_field('schema_nova_post', 'schema_nova_post_nonce');
        $meta = get_post_meta($post->ID, '_schema_nova', true);
        $meta = is_array($meta) ? $meta : [];
        $title = (string) get_post_meta($post->ID, '_schema_nova_title', true);
        $desc  = (string) get_post_meta($post->ID, '_schema_nova_meta_description', true);
        $type  = (string) ($meta['schema_type'] ?? '');
        $permalink = get_permalink($post);
        $test = $permalink ? 'https://search.google.com/test/rich-results?url=' . rawurlencode($permalink) : '';

        echo '<div class="sn-metabox">';
        echo '<div class="sn-serp">';
        echo '<p class="sn-serp__url">' . esc_html(preg_replace('#^https?://#', '', (string) $permalink)) . '</p>';
        echo '<p class="sn-serp__title" id="sn-live-title">' . esc_html($title !== '' ? $title : get_the_title($post)) . '</p>';
        echo '<p class="sn-serp__desc" id="sn-live-desc">' . esc_html($desc !== '' ? $desc : wp_trim_words(wp_strip_all_tags($post->post_content), 28)) . '</p>';
        echo '</div>';

        echo '<label class="sn-field"><span>' . esc_html(sn__('SERP title')) . '</span>';
        echo '<input type="text" name="schema_nova_title" id="sn-title-input" value="' . esc_attr($title) . '" maxlength="70" placeholder="' . esc_attr(get_the_title($post)) . '" /></label>';

        echo '<label class="sn-field"><span>' . esc_html(sn__('Meta description')) . '</span>';
        echo '<textarea name="schema_nova_meta_description" id="sn-desc-input" rows="3" maxlength="180">' . esc_textarea($desc) . '</textarea></label>';

        echo '<label class="sn-field"><span>' . esc_html(sn__('Extra schema type for this page')) . '</span>';
        echo '<select name="schema_nova[schema_type]">';
        $options = [
            '' => sn__('Auto (from post type)'),
            'BlogPosting' => 'BlogPosting',
            'NewsArticle' => 'NewsArticle',
            'Article' => 'Article',
            'Product' => 'Product',
            'Service' => 'Service',
            'Event' => 'Event',
            'JobPosting' => 'JobPosting',
            'Recipe' => 'Recipe',
            'Course' => 'Course',
            'SoftwareApplication' => 'SoftwareApplication',
            'Dataset' => 'Dataset',
            'Review' => 'Review',
            'QAPage' => 'QAPage',
            'FAQPage' => 'FAQPage',
        ];
        foreach ($options as $k => $lab) {
            echo '<option value="' . esc_attr($k) . '"' . selected($type, $k, false) . '>' . esc_html($lab) . '</option>';
        }
        echo '</select></label>';

        echo '<details class="sn-more"><summary>' . esc_html(sn__('Product / Event / Job / Recipe / FAQ fields')) . '</summary>';
        $this->field($meta, 'product_name', sn__('Product name'));
        $this->field($meta, 'sku', 'SKU');
        $this->field($meta, 'brand', sn__('Brand'));
        $this->field($meta, 'price', sn__('Price'));
        $this->field($meta, 'currency', sn__('Currency'), 'IRR');
        $this->field($meta, 'event_name', sn__('Event name'));
        $this->field($meta, 'start_date', sn__('Start (ISO 8601)'));
        $this->field($meta, 'end_date', sn__('End'));
        $this->field($meta, 'event_location', sn__('Event location'));
        $this->field($meta, 'job_title', sn__('Job title'));
        $this->field($meta, 'job_city', sn__('Job city'));
        $this->field($meta, 'salary', sn__('Salary'));
        echo '<label class="sn-field"><span>' . esc_html(sn__('Recipe ingredients (one per line)')) . '</span>';
        echo '<textarea name="schema_nova[recipe_ingredients]" rows="4">' . esc_textarea((string) ($meta['recipe_ingredients'] ?? '')) . '</textarea></label>';
        echo '<label class="sn-field"><span>' . esc_html(sn__('Recipe steps (one per line)')) . '</span>';
        echo '<textarea name="schema_nova[recipe_instructions]" rows="4">' . esc_textarea((string) ($meta['recipe_instructions'] ?? '')) . '</textarea></label>';

        $faqs = is_array($meta['faq'] ?? null) ? $meta['faq'] : [['question' => '', 'answer' => '']];
        echo '<div id="sn-faq-rows">';
        foreach ($faqs as $i => $row) {
            echo '<div class="sn-faq-row">';
            echo '<input type="text" name="schema_nova[faq][' . (int) $i . '][question]" placeholder="' . esc_attr(sn__('Question')) . '" value="' . esc_attr((string) ($row['question'] ?? '')) . '" />';
            echo '<textarea name="schema_nova[faq][' . (int) $i . '][answer]" placeholder="' . esc_attr(sn__('Answer')) . '">' . esc_textarea((string) ($row['answer'] ?? '')) . '</textarea>';
            echo '</div>';
        }
        echo '</div>';
        echo '<p><button type="button" class="button" id="sn-add-faq">' . esc_html(sn__('Add FAQ')) . '</button></p>';
        echo '</details>';

        if ($test) {
            echo '<p><a class="button" target="_blank" rel="noopener" href="' . esc_url($test) . '">' . esc_html(sn__('Test this URL in Google Rich Results')) . '</a></p>';
        }
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function field(array $meta, string $key, string $label, string $placeholder = ''): void
    {
        echo '<label class="sn-field"><span>' . esc_html($label) . '</span>';
        echo '<input type="text" name="schema_nova[' . esc_attr($key) . ']" value="' . esc_attr((string) ($meta[$key] ?? '')) . '" placeholder="' . esc_attr($placeholder) . '" /></label>';
    }

    public function save(int $postId, \WP_Post $post): void
    {
        if (! isset($_POST['schema_nova_post_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['schema_nova_post_nonce'])), 'schema_nova_post')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        $title = sanitize_text_field((string) ($_POST['schema_nova_title'] ?? ''));
        $desc  = sanitize_textarea_field((string) ($_POST['schema_nova_meta_description'] ?? ''));
        if ($title !== '') {
            update_post_meta($postId, '_schema_nova_title', $title);
        } else {
            delete_post_meta($postId, '_schema_nova_title');
        }
        if ($desc !== '') {
            update_post_meta($postId, '_schema_nova_meta_description', $desc);
        } else {
            delete_post_meta($postId, '_schema_nova_meta_description');
        }

        $raw = wp_unslash($_POST['schema_nova'] ?? []);
        if (! is_array($raw)) {
            return;
        }
        $clean = [];
        foreach ($raw as $k => $v) {
            $key = sanitize_key((string) $k);
            if ($key === 'faq' && is_array($v)) {
                $clean['faq'] = [];
                foreach ($v as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $q = sanitize_text_field((string) ($row['question'] ?? ''));
                    $a = sanitize_textarea_field((string) ($row['answer'] ?? ''));
                    if ($q !== '' && $a !== '') {
                        $clean['faq'][] = ['question' => $q, 'answer' => $a];
                    }
                }
                continue;
            }
            if (in_array($key, ['recipe_ingredients', 'recipe_instructions', 'job_description', 'event_description'], true)) {
                $clean[$key] = sanitize_textarea_field((string) $v);
            } else {
                $clean[$key] = sanitize_text_field((string) $v);
            }
        }
        update_post_meta($postId, '_schema_nova', $clean);
    }
}
