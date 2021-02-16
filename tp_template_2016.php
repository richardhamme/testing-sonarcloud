public function get_entry ($interface) {
        // Get the data
        $data = $interface->get_data();
        $label1 = '';
        $label2 = '';
        
        // Define the labels
        if (strpos($data['row']['tp_meta_pub_custom_labels'], 'Open Access') !== false) { 
            $label1 = '<span class="tp_pub_type" style="background-color:red;">Open Access</span>'; 
        }
        if (strpos($data['row']['tp_meta_pub_custom_labels'], 'Second Label') !== false) { 
            $label2 = '<span class="tp_pub_type" style="background-color:red;">Second Label</span>'; 
        }
        
        // Define the entry
        $s = '<tr class="tp_publication">';
        $s .= $interface->get_number('<td class="tp_pub_number">', '.</td>');
        $s .= $interface->get_images('left');
        $s .= '<td class="tp_pub_info">';
        $s .= $interface->get_author('<p class="tp_pub_author">', '</p>');
        $s .= '<p class="tp_pub_title">' . $interface->get_title() . ' ' . $interface->get_type() . ' ' . $interface->get_label('status', array('forthcoming') ) . $label1 . $label2 . '</p>';
        $s .= '<p class="tp_pub_additional">' . $interface->get_meta() . '</p>';
        $s .= '<p class="tp_pub_tags">' . $interface->get_tag_line() . '</p>';
        $s .= $interface->get_infocontainer();
        $s .= $interface->get_images('bottom');
        $s .= '</td>';
        $s .= $interface->get_images('right');
        $s .= '</tr>';
        return $s;
    }