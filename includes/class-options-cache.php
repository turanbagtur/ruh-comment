<?php
/**
 * Ruh Comment - Options Cache Class
 * 
 * Plugin options'larını singleton pattern ile önbelleğe alır.
 * Her request'te sadece bir kez get_option() çağrılır.
 * 
 * @package RuhComment
 * @version 7.0
 */

if (!defined('ABSPATH')) exit;

class Ruh_Options_Cache {
    
    /**
     * Singleton instance
     * @var Ruh_Options_Cache|null
     */
    private static $instance = null;
    
    /**
     * Önbelleğe alınmış seçenekler
     * @var array|null
     */
    private $options = null;
    
    /**
     * Private constructor - singleton pattern
     */
    private function __construct() {}
    
    /**
     * Singleton instance al
     * @return Ruh_Options_Cache
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Tüm seçenekleri al (önbellekli)
     * @return array
     */
    public function get_all() {
        if ($this->options === null) {
            $this->options = get_option('ruh_comment_options', array());
        }
        return $this->options;
    }
    
    /**
     * Belirli bir seçeneği al
     * @param string $key Seçenek anahtarı
     * @param mixed $default Varsayılan değer
     * @return mixed
     */
    public function get($key, $default = null) {
        $options = $this->get_all();
        return $options[$key] ?? $default;
    }
    
    /**
     * Seçenek değerini güncelle
     * @param string $key Seçenek anahtarı
     * @param mixed $value Değer
     * @return bool
     */
    public function set($key, $value) {
        $options = $this->get_all();
        $options[$key] = $value;
        $this->options = $options;
        return update_option('ruh_comment_options', $options);
    }
    
    /**
     * Birden fazla seçeneği güncelle
     * @param array $new_options Yeni seçenekler
     * @return bool
     */
    public function update($new_options) {
        $options = $this->get_all();
        $options = array_merge($options, $new_options);
        $this->options = $options;
        return update_option('ruh_comment_options', $options);
    }
    
    /**
     * Önbelleği temizle (flush)
     * WordPress options güncellendiğinde çağrılmalı
     * @return void
     */
    public function flush() {
        $this->options = null;
    }
    
    /**
     * Dil ayarını al
     * @return string 'tr_TR' veya 'en_US'
     */
    public function get_language() {
        return $this->get('language', 'tr_TR');
    }
    
    /**
     * Çoklu dil etkin mi?
     * @return bool
     */
    public function is_multilingual() {
        return $this->get_language() === 'en_US';
    }
    
    /**
     * Belirli bir özelliğin etkin olup olmadığını kontrol et
     * @param string $feature Özellik adı
     * @return bool
     */
    public function is_feature_enabled($feature) {
        return (bool) $this->get('enable_' . $feature, true);
    }
}

/**
 * Global helper fonksiyonu
 * @param string|null $key Seçenek anahtarı (null ise tüm seçenekler)
 * @param mixed $default Varsayılan değer
 * @return mixed
 */
function ruh_get_options($key = null, $default = null) {
    $cache = Ruh_Options_Cache::get_instance();
    
    if ($key === null) {
        return $cache->get_all();
    }
    
    return $cache->get($key, $default);
}

/**
 * Options yardımcı fonksiyonu
 * Tek seferlik çağrı için kısayol
 * @param string $key Seçenek anahtarı
 * @param mixed $default Varsayılan değer
 * @return mixed
 */
function ruh_option($key, $default = null) {
    return ruh_get_options($key, $default);
}

/**
 * Options'ı güncelle ve cache'i temizle
 * @param string $key Seçenek anahtarı
 * @param mixed $value Değer
 * @return bool
 */
function ruh_update_option($key, $value) {
    $cache = Ruh_Options_Cache::get_instance();
    $result = $cache->set($key, $value);
    $cache->flush();
    return $result;
}

/**
 * Dil çeviri helper'ı
 * Options'dan dil ayarını alır ve uygun metni döndürür
 * @param string $tr Türkçe metin
 * @param string $en İngilizce metin
 * @return string
 */
function ruh_translate($tr, $en) {
    $cache = Ruh_Options_Cache::get_instance();
    return $cache->is_multilingual() ? $en : $tr;
}

/**
 * Kısa çeviri helper - tek harf
 * @param string $tr Türkçe metin
 * @param string $en İngilizce metin
 * @return string
 */
function ruh_t($tr, $en = '') {
    return ruh_translate($tr, $en ?: $tr);
}