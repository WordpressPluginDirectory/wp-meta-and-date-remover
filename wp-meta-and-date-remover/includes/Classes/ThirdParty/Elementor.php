<?php
namespace WPMDRMain\Classes\ThirdParty;

class Elementor
{


    function __construct()
    {
        add_action('elementor/documents/register_controls', [$this, 'register_document_controls']);
        add_action( 'elementor/editor/after_save', [$this, 'interceptSave'], 10, 2);
    }

    function register_document_controls($document)
    {

        if (!$document instanceof \Elementor\Core\DocumentTypes\PageBase || !$document::get_property('has_elements')) {
            return;
        }

        $document->start_controls_section(
            'wpmdr_section_settings',
            [
                'label' => sprintf(__('WP Meta and Date Remover %s', 'wp-meta-and-date-remover'), wpmdr_fs()->is_not_paying() ? '<span style="background-color: #e6a23c; padding: 3px; color: #fff; border-radius: 3px">Pro</span>' : ''),
                'tab' => \Elementor\Controls_Manager::TAB_SETTINGS,
            ]
        );

        if (wpmdr_fs()->is_not_paying()) {
            $document->add_control(
                'wpmdr_upgrade_to_pro_info',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => '<p style="color:red">These settings will not be applied with free plan</p>',
                    'condition' => ['wpmdr_remove_meta_and_date' => 'yes'],
                ]
            );
        }

        $document->add_control(
            'wpmdr_remove_meta_and_date',
            [
                'label' => esc_html__('Remove meta and date', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wp-meta-and-date-remover'),
                'label_off' => __('No', 'wp-meta-and-date-remover'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $document->add_control(
            'wpmdr_remove_date',
            [
                'label' => esc_html__('Remove Date', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wp-meta-and-date-remover'),
                'label_off' => __('No', 'wp-meta-and-date-remover'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'wpmdr_remove_meta_and_date' => 'yes',
                ],
            ]
        );

        $document->add_control(
            'wpmdr_remove_author',
            [
                'label' => esc_html__('Remove Author', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wp-meta-and-date-remover'),
                'label_off' => __('No', 'wp-meta-and-date-remover'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'wpmdr_remove_meta_and_date' => 'yes',
                ],
            ]
        );

        $document->add_control(
            'wpmdr_remove_yoast_datePublished',
            [
                'label' => esc_html__('Remove Yoast Published Date', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wp-meta-and-date-remover'),
                'label_off' => __('No', 'wp-meta-and-date-remover'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'wpmdr_remove_meta_and_date' => 'yes',
                ],
            ]
        );

        $document->add_control(
            'wpmdr_remove_yoast_dateModified',
            [
                'label' => esc_html__('Remove Yoast Modified Date', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wp-meta-and-date-remover'),
                'label_off' => __('No', 'wp-meta-and-date-remover'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'wpmdr_remove_meta_and_date' => 'yes',
                ],
            ]
        );

        if (wpmdr_fs()->is_not_paying()) {
            $document->add_control(
                'wpmdr_upgrade_to_pro',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => '<p>Upgrade to <a href="' . wpmdr_fs()->get_upgrade_url() . '" target="_blank">WP Meta and Date Remover Pro</a> to unlock these features</p><div style="text-align: center; padding-top: 10px"><a style="background-color: #e6a23c; padding: 3px; color: #fff; border-radius: 3px" href="' . wpmdr_fs()->get_upgrade_url() . '">Upgrade now</a></div>',
                ]
            );
        }

        $document->end_controls_section();
    }


    function interceptSave($post_id, $editor_data) {
        $pageSettings = get_post_meta($post_id, '_elementor_page_settings', true);
        update_post_meta($post_id, 'intercepted', json_encode($pageSettings));
        if (empty($pageSettings)) {
            return;
        }
        $blockEditorSettings = array(
            "individualPostRemove"=>$pageSettings['wpmdr_remove_meta_and_date'] == 'yes'? 1 : 0,
            "individualPostRemoveDate"=>$pageSettings['wpmdr_remove_date'] == 'yes'? 1 : 0,
            "individualPostRemoveAuthor"=>$pageSettings['wpmdr_remove_author'] == 'yes'? 1 : 0,
            "individualPostYoastRemovePublished"=>$pageSettings['wpmdr_remove_yoast_datePublished'] == 'yes'? 1 : 0,
            "individualPostYoastRemoveModified"=>$pageSettings['wpmdr_remove_yoast_dateModified'] == 'yes'? 1 : 0
        );
        update_post_meta($post_id, 'wpmdr_menu_extended', $blockEditorSettings);
        update_post_meta($post_id, 'wpmdr_menu', $blockEditorSettings['individualPostRemove']);
    }

}