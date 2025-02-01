<?php 


/***************************
  **** WP FORM BUILDER CLASS
  ****
  **

1. `text`
2. `email`
3. `number`
4. `textarea`
5. `wysiwyg`
6. `radio`
7. `checkbox`
8. `checkbox_multiselect`
9. `select`
10. `colorpicker`
11. `upload`
12. `date`
13. `dynamic_select`
14. `dynamic_multiselect`
  */

  class WpFormBuilder {
    private $fields = [];

    public function field($args) {
        $defaults = array(
            'type'        => 'text',
            'label'       => '',
            'name'        => '',
            'id'          => '',
            'class'       => '',
            'description' => '',
            'value'       => '',
            'options'     => array()
        );

        $args = wp_parse_args($args, $defaults);

        // Handle dynamic_select options
        if ($args['type'] === 'dynamic_select' && is_string($args['options']) || $args['type'] === 'dynamic_multiselect' ) {
            $args['options'] = $this->get_dynamic_options($args['options']);
        }

        $this->fields[] = $args;
    }

    private function get_dynamic_options($data_type) {
        $options = array();

        switch ($data_type) {
            case 'all_users':
            $users = get_users();
            foreach ($users as $user) {
                $options[$user->ID] = $user->display_name;
            }
            break;

            case 'all_custom_post_types':
                $post_types = get_post_types(array('_builtin' => false), 'objects');
                foreach ($post_types as $post_type) {
                    $options[$post_type->name] = $post_type->label;
                }
            break;

            case 'all_post_types':
                $post_types = get_post_types(array('public' => true), 'objects');
                foreach ($post_types as $post_type) {
                    $options[$post_type->name] = $post_type->label;
                }
            break;

            case 'all_taxonomies':
                $taxonomies = get_taxonomies(array('public' => true), 'objects');
                foreach ($taxonomies as $taxonomy) {
                    $options[$taxonomy->name] = $taxonomy->label;
                }
            break;

            case 'all_posts':
                    $posts = get_posts(array('numberposts' => -1));
                    foreach ($posts as $post) {
                        $options[$post->ID] = $post->post_title;
                    }
            break;
            
            case 'all_terms':
                    $terms = get_terms(array('taxonomy' => get_taxonomies(), 'hide_empty' => false));
                    foreach ($terms as $term) {
                        $options[$term->term_id] = $term->name;
                    }
            break;

            case 'all_pages':
                $pages = get_pages();
                foreach ($pages as $page) {
                    $options[$page->ID] = $page->post_title;
                }
                break;
                case 'all_post_type_titles':
                    $post_types = get_post_types(array('public' => true), 'objects');
                    foreach ($post_types as $post_type) {
                        $posts = get_posts(array('post_type' => $post_type->name, 'numberposts' => -1));
                        foreach ($posts as $post) {
                            $options[$post->ID] = $post->post_title;
                        }
                    }
                    break;
                                                                            
            default:
            break;
        }

        return $options;
    }

    public function render() {
        echo '<table class="form-table">';
        foreach ($this->fields as $field) {
            echo '<tr>';
            echo '<th scope="row"><label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . '</label></th>';
            echo '<td>';
            switch ($field['type']) {
                case 'text':
                case 'email':
                case 'number':
                    echo '<input class="wp-form-builder-field field-'.$field['type'].'" type="' . esc_attr($field['type']) . '" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field['value']) . '">';
                    break;
                case 'textarea':
                    echo '<textarea class="wp-form-builder-field field-'.$field['type'].'" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '">' . esc_textarea($field['value']) . '</textarea>';
                    break;
                case 'wysiwyg':
                    wp_editor($field['value'], $field['id'], ['textarea_name' => $field['name']]);
                    break;
                case 'radio':
                case 'checkbox':
                    foreach ($field['options'] as $label => $value) {
                        echo '<label><input type="' . esc_attr($field['type']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '"' . checked($field['value'], $value, false) . '> ' . esc_html($label) . '</label><br>';
                    }
                    break;
                case 'checkbox_multiselect':
                    foreach ($field['options'] as $label => $value) {
                        echo '<label>
                        <input type="checkbox" name="' . esc_attr($field['name']) . '[]" value="' . ($value) . '"' . (in_array($value, (array)$field['value']) ? 'checked' : '') . '> '
                         . esc_html($label) . 
                        '</label>
                        <br>';
                    }
                break;

                case 'dynamic_multiselect':
                    foreach ($field['options'] as $label => $value) {
                        echo '<label>
                        <input type="checkbox" name="' . esc_attr($field['name']) . '[]" value="' . ($value) . '"' . (in_array($value, (array)$field['value']) ? 'checked' : '') . '> '
                            . ucwords($label) . 
                        '</label>
                        <br>';
                    }
                break;

                case 'select':
                case 'dynamic_select':
                    echo '<select class="wp-form-builder-field field-'.$field['type'].'" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '">';
                    echo '<option value="">-- Select --</option>';
                    foreach ($field['options'] as $value => $label) {
                        echo '<option value="' . esc_attr($value) . '"' . selected($field['value'], $value, false) . '>' . ucwords($label) . '</option>';
                    }
                    echo '</select>';
                    break;
               
                case 'colorpicker':
                    echo '<input type="text" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="color-picker" value="' . esc_attr($field['value']) . '">';
                    break;
               
                case 'upload':
                    $value = $field['value'];
                    echo '<input type="text" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" />';
                    echo '<input type="button" id="' . esc_attr($field['id']) . '_button" value="Upload" class="button button-primary wp-upload-button" />';
                    echo '<div id="' . esc_attr($field['id']) . '_preview">';
                    if ($value) {
                        foreach (explode(',', $value) as $file) {
                            echo '<div class="uploaded-file"><img src="' . esc_url($file) . '" style="max-width:100px;" /><span style="cursor:pointer" class="remove-file" data-file="' . esc_attr($file) . '">Remove</span></div>';
                        }
                    }
                    echo '</div>';
                    break;

                case 'date':
                    echo '<input class="wp-form-builder-field field-'.$field['type'].'" type="date" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field['value']) . '">';
                    break;
           
                }
            echo '</td><td>' . $field['description'] . '</td></tr>';
        }
        echo '</table>';
    }
}


  
/***
 * EXAMPLE
 * ***/
/*
$fb = new WpFormBuilder();

    $fb->field(array(
        'name' => 'text_field',
        'label' => 'Text Field',
        'type' => 'text',
    ));

    $fb->field(array(
        'name' => 'email_field',
        'label' => 'Email Field',
        'type' => 'email',
    ));

    $fb->field(array(
        'name' => 'number_field',
        'label' => 'Number Field',
        'type' => 'number',
    ));

    $fb->field(array(
        'name' => 'textarea_field',
        'label' => 'Textarea Field',
        'type' => 'textarea',
    ));

    $fb->field(array(
        'name' => 'wysiwyg_field',
        'label' => 'WYSIWYG Field',
        'type' => 'wysiwyg',
    ));

    $fb->field(array(
        'name' => 'radio_field',
        'label' => 'Radio Field',
        'type' => 'radio',
        'options' => array(
            'option1' => 'Option 1',
            'option2' => 'Option 2'
        ),
    ));

    $fb->field(array(
        'name' => 'checkbox_field',
        'label' => 'Checkbox Field',
        'type' => 'checkbox',
        'options' => array(
            'option1' => 'Option 1',
            'option2' => 'Option 2'
        ),
    ));

    $fb->field(array(
        'name' => 'checkbox_multiselect_field',
        'label' => 'Checkbox Multiselect Field',
        'type' => 'checkbox_multiselect',
        'options' => array(
            'option1' => 'Option 1',
            'option2' => 'Option 2'
        ),
    ));

    $fb->field(array(
        'name' => 'select_field',
        'label' => 'Select Field',
        'type' => 'select',
        'options' => array(
            'option1' => 'Option 1',
            'option2' => 'Option 2'
        ),
    ));

    $fb->field(array(
        'name' => 'colorpicker_field',
        'label' => 'Color Picker Field',
        'type' => 'colorpicker',
    ));

    $fb->field(array(
        'name' => 'upload_field',
        'label' => 'Upload Field',
        'type' => 'upload',
    ));

    $fb->field(array(
        'name' => 'date_field',
        'label' => 'Date Field',
        'type' => 'date',
    ));

    // Usage example:
$form_builder = new WpFormBuilder();

// Adding dynamic select fields
$form_builder->field([
    'type'    => 'dynamic_select',
    'label'   => 'All Users',
    'name'    => 'all_users',
    'id'      => 'all_users',
    'options' => 'all_users'
]);


$form_builder->field([
    'type'    => 'dynamic_multiselect',
    'label'   => 'All Users',
    'name'    => 'all_users',
    'id'      => 'all_users',
    'options' => 'all_users'
]);


$form_builder->field([
    'type'    => 'dynamic_select',
    'label'   => 'All Custom Post Types',
    'name'    => 'all_custom_post_types',
    'id'      => 'all_custom_post_types',
    'options' => 'all_custom_post_types'
]);

$form_builder->field([
    'type'    => 'dynamic_select',
    'label'   => 'All Post Types',
    'name'    => 'all_post_types',
    'id'      => 'all_post_types',
    'options' => 'all_post_types'
]);

$form_builder->field([
    'type'    => 'dynamic_select',
    'label'   => 'All Taxonomies',
    'name'    => 'all_taxonomies',
    'id'      => 'all_taxonomies',
    'options' => 'all_taxonomies'
]);

// Render the form
$form_builder->render();


*/