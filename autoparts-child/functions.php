<?php
/**
 * Child-Theme functions and definitions
 */

function autoparts_child_scripts() {
    wp_enqueue_style( 'autoparts-style', get_template_directory_uri(). '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'autoparts_child_scripts' );
?>