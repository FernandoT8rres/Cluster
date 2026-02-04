<?php
/**
 * Output Sanitizer
 * Sanitización segura de salida para prevenir XSS
 * 
 * Uso:
 * require_once 'utils/output-sanitizer.php';
 * echo OutputSanitizer::html($userInput);
 */

class OutputSanitizer {
    
    /**
     * Sanitiza texto para salida HTML
     * @param mixed $text Texto a sanitizar
     * @return string Texto sanitizado
     */
    public static function html($text) {
        if ($text === null) {
            return '';
        }
        
        return htmlspecialchars((string)$text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitiza texto para uso en JavaScript
     * @param mixed $data Datos a sanitizar
     * @return string JSON sanitizado
     */
    public static function js($data) {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Sanitiza URL
     * @param mixed $url URL a sanitizar
     * @return string URL sanitizada
     */
    public static function url($url) {
        if ($url === null || $url === '') {
            return '';
        }
        
        return filter_var((string)$url, FILTER_SANITIZE_URL);
    }
    
    /**
     * Sanitiza atributo HTML
     * @param mixed $attr Atributo a sanitizar
     * @return string Atributo sanitizado
     */
    public static function attr($attr) {
        if ($attr === null) {
            return '';
        }
        
        return htmlspecialchars((string)$attr, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitiza HTML permitiendo solo tags seguros
     * @param mixed $html HTML a sanitizar
     * @param array $allowedTags Tags permitidos
     * @return string HTML sanitizado
     */
    public static function safeHtml($html, array $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'a', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']) {
        if ($html === null) {
            return '';
        }
        
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        return strip_tags((string)$html, $allowedTagsString);
    }
}
?>
