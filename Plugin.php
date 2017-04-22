<?php
/**
 * Structure Data
 * 
 * @package StructureData
 * @author Joker Qyou
 * @version 0.0.1
 * @link https://blog.mynook.info/
 */

class StructureData_Plugin implements Typecho_Plugin_Interface
{

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        // Register $this->header to be called when processing
        // instance of Widget_Archive class.
        // Typecho_Plugin::factory('Widget_Abstract_Contents')->header = array('StructureData_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->header = array('StructureData_Plugin', 'header');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * Output JSON-LD structure data
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function header($header, $post) {
        // Dates and headline
        $datePublished = date('c', $post->created);
        $dateModified = date('c', $post->modified ? $post->modified : $post->created);
        $headLine = $post->title;

        // webpage id (permanent link)
        $postPermaLink = $post->permalink;

        // Author name and translator name
        $authorName = $post->fields->author ? $post->fields->author : $post->author->screenName;
        $translatorName = $post->fields->translator ? $post->fields->translator : $post->author->screenName;
        $translated = $post->fields->translated;

        // Publisher
        $publisherName = Typecho_Widget::widget('Widget_Options')->title;
        $publisherLogo = '';

        // Main image of current blog post
        // Use `image` custom field value if presented
        if($post->fields->image){
            $imageURL = $post->fields->image;
        }else{
            // Try to find the first image attachment
            $imageURL = '';
            // Fallback to a default value configured by the user
        }
        $imageHeight = $post->fields->imageHeight ? $post->fields->imageHeight : 200;
        $imageWidth = $post->fields->imageWidth ? $post->fields->imageWidth : 696;

        echo <<<JSONLD_HEAD
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "BlogPosting",
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{$postPermaLink}"
    },
    "headline": "{$headLine}",
    "datePublished": "{$datePublished}",
    "dateModified": "{$dateModified}",
    "author": {
        "@type": "Person",
        "name": "{$authorName}"
    },

JSONLD_HEAD;

        if($translated){
            echo <<<JSONLD_TRANSLATOR
    "translator": {
        "@type": "Person",
        "name": "{$translatorName}"
    },

JSONLD_TRANSLATOR;
        }


        echo <<<JSONLD_END
    "publisher": {
        "@type": "Organization",
        "name": "{$publisherName}",
        "logo": {
            "@type": "ImageObject",
            "url": "{$publisherLogo}"
        }
    },
    "image": {
        "@type": "ImageObject",
        "url": "{$imageURL}",
        "height": {$imageHeight},
        "width": {$imageWidth}
    }
}
</script>
JSONLD_END;
    }
    
}

?>
