<?php
/**
 * Plugin Name: WhatsApp Float Button (Private)
 * Description: Botón flotante de WhatsApp con estilos, animaciones y mensajes configurables para uso privado.
 * Version: 1.0.0
 * Author: Felipe Godoy
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Menú en admin
add_action('admin_menu', function () {
    add_options_page(
        'WhatsApp Float Button',
        'WhatsApp Float Button',
        'manage_options',
        'wafb-settings',
        'wafb_render_settings_page'
    );
});

// 2. Registrar ajustes
add_action('admin_init', function () {
    register_setting('wafb_settings_group', 'wafb_phone');
    register_setting('wafb_settings_group', 'wafb_message');
    register_setting('wafb_settings_group', 'wafb_show_on_home');
});


function wafb_render_settings_page() { ?>
    <div class="wrap">
        <h1>WhatsApp Float Button</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wafb_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th>Número WhatsApp (con código país)</th>
                    <td><input type="text" name="wafb_phone" value="<?php echo esc_attr(get_option('wafb_phone', '')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Mensaje por defecto</th>
                    <td><input type="text" name="wafb_message" value="<?php echo esc_attr(get_option('wafb_message', 'Hola, quiero más información')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Mostrar solo en portada</th>
                    <td><input type="checkbox" name="wafb_show_on_home" value="1" <?php checked(get_option('wafb_show_on_home', 0), 1); ?>></td>
                </tr>
            </table>
            <?php submit_button('Guardar cambios'); ?>
        </form>
    </div>
<?php }


class WFWP_WhatsApp_Float_Button
{
    private const OPTION_NAME = 'wfwp_options';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_button']);
    }

    public static function defaults(): array
    {
        return [
            'enabled' => '1',
            'phone' => '',
            'message_template' => 'Hola, necesito más información sobre {page_title}.',
            'button_label' => 'Escríbenos por WhatsApp',
            'position' => 'right',
            'offset_x' => 24,
            'offset_y' => 24,
            'background_color' => '#25D366',
            'text_color' => '#FFFFFF',
            'icon_color' => '#FFFFFF',
            'font_size' => 15,
            'icon_size' => 22,
            'padding_y' => 12,
            'padding_x' => 16,
            'border_radius' => 999,
            'shadow' => '1',
            'animation' => 'pulse',
            'animation_duration' => 1.8,
            'display_on_mobile' => '1',
            'display_on_desktop' => '1',
            'open_new_tab' => '1',
            'custom_css' => '',
        ];
    }

    public function get_options(): array
    {
        $saved = get_option(self::OPTION_NAME, []);
        return wp_parse_args(is_array($saved) ? $saved : [], self::defaults());
    }

    public function register_admin_menu(): void
    {
        add_options_page(
            'WhatsApp Float Button',
            'WhatsApp Float Button',
            'manage_options',
            'wfwp-whatsapp-float',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'wfwp_settings_group',
            self::OPTION_NAME,
            [
                'sanitize_callback' => [$this, 'sanitize_options'],
                'default' => self::defaults(),
            ]
        );

        add_settings_section(
            'wfwp_general',
            'Configuración general',
            function () {
                echo '<p>Configura teléfono, mensajes y visibilidad del botón de WhatsApp.</p>';
            },
            'wfwp-whatsapp-float'
        );

        add_settings_section(
            'wfwp_style',
            'Estilos y animación',
            function () {
                echo '<p>Personaliza el aspecto visual del botón flotante.</p>';
            },
            'wfwp-whatsapp-float'
        );

        $this->add_field('enabled', 'Activar botón', 'checkbox', 'wfwp_general');
        $this->add_field('phone', 'Teléfono WhatsApp (con código país)', 'text', 'wfwp_general');
        $this->add_field('message_template', 'Mensaje editable', 'textarea', 'wfwp_general');
        $this->add_field('button_label', 'Texto del botón', 'text', 'wfwp_general');
        $this->add_field('display_on_mobile', 'Mostrar en móviles', 'checkbox', 'wfwp_general');
        $this->add_field('display_on_desktop', 'Mostrar en escritorio', 'checkbox', 'wfwp_general');
        $this->add_field('open_new_tab', 'Abrir en nueva pestaña', 'checkbox', 'wfwp_general');

        $this->add_field('position', 'Posición horizontal', 'select', 'wfwp_style', [
            'left' => 'Izquierda',
            'right' => 'Derecha',
        ]);
        $this->add_field('offset_x', 'Separación horizontal (px)', 'number', 'wfwp_style');
        $this->add_field('offset_y', 'Separación vertical (px)', 'number', 'wfwp_style');
        $this->add_field('background_color', 'Color de fondo', 'color', 'wfwp_style');
        $this->add_field('text_color', 'Color del texto', 'color', 'wfwp_style');
        $this->add_field('icon_color', 'Color del icono', 'color', 'wfwp_style');
        $this->add_field('font_size', 'Tamaño de texto (px)', 'number', 'wfwp_style');
        $this->add_field('icon_size', 'Tamaño del icono (px)', 'number', 'wfwp_style');
        $this->add_field('padding_y', 'Padding vertical (px)', 'number', 'wfwp_style');
        $this->add_field('padding_x', 'Padding horizontal (px)', 'number', 'wfwp_style');
        $this->add_field('border_radius', 'Radio de borde (px)', 'number', 'wfwp_style');
        $this->add_field('shadow', 'Mostrar sombra', 'checkbox', 'wfwp_style');
        $this->add_field('animation', 'Animación', 'select', 'wfwp_style', [
            'none' => 'Sin animación',
            'pulse' => 'Pulse',
            'bounce' => 'Bounce',
            'shake' => 'Shake',
        ]);
        $this->add_field('animation_duration', 'Duración animación (s)', 'number', 'wfwp_style', [], '0.1');
        $this->add_field('custom_css', 'CSS personalizado', 'textarea', 'wfwp_style');
    }

    private function add_field(string $key, string $label, string $type, string $section, array $choices = [], string $step = '1'): void
    {
        add_settings_field(
            $key,
            $label,
            [$this, 'render_field'],
            'wfwp-whatsapp-float',
            $section,
            [
                'key' => $key,
                'type' => $type,
                'choices' => $choices,
                'step' => $step,
            ]
        );
    }

    public function sanitize_options($input): array
    {
        $defaults = self::defaults();
        $input = is_array($input) ? $input : [];

        return [
            'enabled' => empty($input['enabled']) ? '0' : '1',
            'phone' => preg_replace('/[^0-9]/', '', (string)($input['phone'] ?? '')),
            'message_template' => sanitize_textarea_field($input['message_template'] ?? $defaults['message_template']),
            'button_label' => sanitize_text_field($input['button_label'] ?? $defaults['button_label']),
            'position' => in_array(($input['position'] ?? ''), ['left', 'right'], true) ? $input['position'] : $defaults['position'],
            'offset_x' => absint($input['offset_x'] ?? $defaults['offset_x']),
            'offset_y' => absint($input['offset_y'] ?? $defaults['offset_y']),
            'background_color' => sanitize_hex_color($input['background_color'] ?? $defaults['background_color']) ?: $defaults['background_color'],
            'text_color' => sanitize_hex_color($input['text_color'] ?? $defaults['text_color']) ?: $defaults['text_color'],
            'icon_color' => sanitize_hex_color($input['icon_color'] ?? $defaults['icon_color']) ?: $defaults['icon_color'],
            'font_size' => max(10, absint($input['font_size'] ?? $defaults['font_size'])),
            'icon_size' => max(12, absint($input['icon_size'] ?? $defaults['icon_size'])),
            'padding_y' => max(6, absint($input['padding_y'] ?? $defaults['padding_y'])),
            'padding_x' => max(8, absint($input['padding_x'] ?? $defaults['padding_x'])),
            'border_radius' => absint($input['border_radius'] ?? $defaults['border_radius']),
            'shadow' => empty($input['shadow']) ? '0' : '1',
            'animation' => in_array(($input['animation'] ?? ''), ['none', 'pulse', 'bounce', 'shake'], true) ? $input['animation'] : $defaults['animation'],
            'animation_duration' => max(0.1, (float)($input['animation_duration'] ?? $defaults['animation_duration'])),
            'display_on_mobile' => empty($input['display_on_mobile']) ? '0' : '1',
            'display_on_desktop' => empty($input['display_on_desktop']) ? '0' : '1',
            'open_new_tab' => empty($input['open_new_tab']) ? '0' : '1',
            'custom_css' => wp_strip_all_tags((string)($input['custom_css'] ?? ''), true),
        ];
    }

    public function render_field(array $args): void
    {
        $options = $this->get_options();
        $key = $args['key'];
        $type = $args['type'];
        $value = $options[$key] ?? '';
        $name = self::OPTION_NAME . '[' . $key . ']';

        switch ($type) {
            case 'checkbox':
                printf(
                    '<label><input type="checkbox" name="%s" value="1" %s /> Activado</label>',
                    esc_attr($name),
                    checked($value, '1', false)
                );
                break;
            case 'textarea':
                printf(
                    '<textarea name="%s" rows="4" class="large-text">%s</textarea>',
                    esc_attr($name),
                    esc_textarea((string)$value)
                );
                if ($key === 'message_template') {
                    echo '<p class="description">Variables disponibles: <code>{page_title}</code>, <code>{site_name}</code>, <code>{url}</code>.</p>';
                }
                break;
            case 'select':
                echo '<select name="' . esc_attr($name) . '">';
                foreach ($args['choices'] as $choiceValue => $choiceLabel) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($choiceValue),
                        selected((string)$value, (string)$choiceValue, false),
                        esc_html($choiceLabel)
                    );
                }
                echo '</select>';
                break;
            default:
                $inputType = in_array($type, ['text', 'number', 'color'], true) ? $type : 'text';
                $step = $type === 'number' ? ' step="' . esc_attr($args['step']) . '"' : '';
                printf(
                    '<input type="%s" name="%s" value="%s" class="regular-text"%s />',
                    esc_attr($inputType),
                    esc_attr($name),
                    esc_attr((string)$value),
                    $step
                );
                break;
        }
    }

    public function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>WhatsApp Float Button</h1>';
        echo '<form action="options.php" method="post">';
        settings_fields('wfwp_settings_group');
        do_settings_sections('wfwp-whatsapp-float');
        submit_button('Guardar cambios');
        echo '</form>';
        echo '</div>';
    }

    public function enqueue_assets(): void
    {
        $options = $this->get_options();

        if ($options['enabled'] !== '1' || empty($options['phone'])) {
            return;
        }

        wp_register_style('wfwp-whatsapp-float-inline', false, [], '1.0.0');
        wp_enqueue_style('wfwp-whatsapp-float-inline');

        $positionRule = $options['position'] === 'left'
            ? 'left:' . intval($options['offset_x']) . 'px;right:auto;'
            : 'right:' . intval($options['offset_x']) . 'px;left:auto;';

        $shadow = $options['shadow'] === '1' ? '0 10px 25px rgba(0,0,0,.2)' : 'none';
        $animation = $this->animation_css($options['animation'], (float)$options['animation_duration']);

        $customCss = trim((string)$options['custom_css']);

        $css = '
            .wfwp-whatsapp-btn{
                position:fixed;
                bottom:' . intval($options['offset_y']) . 'px;
                ' . $positionRule . '
                z-index:9999;
                display:inline-flex;
                align-items:center;
                gap:10px;
                text-decoration:none;
                background:' . esc_html($options['background_color']) . ';
                color:' . esc_html($options['text_color']) . ';
                padding:' . intval($options['padding_y']) . 'px ' . intval($options['padding_x']) . 'px;
                border-radius:' . intval($options['border_radius']) . 'px;
                box-shadow:' . $shadow . ';
                font-size:' . intval($options['font_size']) . 'px;
                line-height:1;
                font-weight:600;
                transition:transform .2s ease, filter .2s ease;
                ' . $animation . '
            }
            .wfwp-whatsapp-btn:hover{transform:translateY(-2px);filter:brightness(1.03);}
            .wfwp-whatsapp-btn .wfwp-icon{width:' . intval($options['icon_size']) . 'px;height:' . intval($options['icon_size']) . 'px;display:block;fill:' . esc_html($options['icon_color']) . ';}
            @media (max-width: 782px) {
                .wfwp-whatsapp-btn.wfwp-hide-mobile{display:none;}
            }
            @media (min-width: 783px) {
                .wfwp-whatsapp-btn.wfwp-hide-desktop{display:none;}
            }
            @keyframes wfwp-pulse {0%,100%{transform:scale(1);}50%{transform:scale(1.04);}}
            @keyframes wfwp-bounce {0%,100%{transform:translateY(0);}50%{transform:translateY(-6px);}}
            @keyframes wfwp-shake {0%,100%{transform:rotate(0);}25%{transform:rotate(-4deg);}75%{transform:rotate(4deg);}}
        ';

        if ($customCss !== '') {
            $css .= "\n" . $customCss;
        }

        wp_add_inline_style('wfwp-whatsapp-float-inline', $css);
    }

    private function animation_css(string $animation, float $duration): string
    {
        if ($animation === 'none') {
            return '';
        }

        return 'animation:wfwp-' . $animation . ' ' . max(0.1, $duration) . 's infinite;';
    }

    public function render_button(): void
    {
        $options = $this->get_options();

        if ($options['enabled'] !== '1' || empty($options['phone'])) {
            return;
        }

        $classes = ['wfwp-whatsapp-btn'];

        if ($options['display_on_mobile'] !== '1') {
            $classes[] = 'wfwp-hide-mobile';
        }

        if ($options['display_on_desktop'] !== '1') {
            $classes[] = 'wfwp-hide-desktop';
        }

        $message = $this->compile_message_template((string)$options['message_template']);
        $url = 'https://wa.me/' . rawurlencode((string)$options['phone']) . '?text=' . rawurlencode($message);

        printf(
            '<a class="%s" href="%s" aria-label="WhatsApp" %s rel="noopener noreferrer">%s<span>%s</span></a>',
            esc_attr(implode(' ', $classes)),
            esc_url($url),
            $options['open_new_tab'] === '1' ? 'target="_blank"' : '',
            $this->icon_svg(),
            esc_html((string)$options['button_label'])
        );
    }

    private function compile_message_template(string $template): string
    {
        $replacements = [
            '{page_title}' => wp_strip_all_tags(get_the_title() ?: ''),
            '{site_name}' => wp_strip_all_tags(get_bloginfo('name')),
            '{url}' => esc_url_raw(home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''))),
        ];

        return strtr($template, $replacements);
    }

    private function icon_svg(): string
    {
        return '<svg class="wfwp-icon" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path d="M16.04 3.2A12.78 12.78 0 0 0 5.12 22.67L3.2 28.8l6.33-1.85A12.8 12.8 0 1 0 16.04 3.2zm0 23.2a10.34 10.34 0 0 1-5.26-1.44l-.38-.22-3.76 1.1 1.12-3.67-.24-.38a10.4 10.4 0 1 1 8.52 4.61zm5.7-7.8c-.31-.16-1.86-.91-2.14-1.02-.29-.1-.5-.16-.71.16-.2.31-.8 1.01-.98 1.22-.18.2-.35.23-.66.08-.3-.16-1.3-.48-2.47-1.54-.91-.8-1.53-1.8-1.7-2.1-.18-.3-.02-.47.14-.62.14-.14.31-.35.47-.52.16-.19.21-.31.31-.52.1-.2.05-.39-.03-.55-.08-.16-.71-1.72-.98-2.35-.25-.6-.5-.52-.71-.53h-.6c-.2 0-.52.08-.8.39-.27.31-1.04 1.02-1.04 2.48 0 1.45 1.06 2.86 1.2 3.06.16.2 2.1 3.2 5.1 4.48.71.31 1.27.5 1.7.64.72.22 1.37.19 1.88.12.58-.09 1.86-.76 2.12-1.48.27-.73.27-1.35.19-1.48-.07-.12-.28-.2-.59-.35z"></path></svg>';
    }
}

new WFWP_WhatsApp_Float_Button();


// Shortcode: [whatsapp_float_button]
add_shortcode('whatsapp_float_button', function () {
    $phone   = preg_replace('/\D+/', '', get_option('wafb_phone', ''));
    $message = rawurlencode(get_option('wafb_message', 'Hola, quiero más información'));

    if (!$phone) return ''; // evita mostrar si no hay número

    $url = "https://wa.me/{$phone}?text={$message}";

    return '<a href="'.esc_url($url).'" class="wafb-btn" target="_blank" rel="noopener noreferrer" aria-label="Chat en WhatsApp">💬</a>';
});

