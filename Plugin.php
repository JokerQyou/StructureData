<?php
/**
 * Structure Data
 * 
 * @package StructureData
 * @author Joker Qyou
 * @version 0.3.0
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
        Typecho_Plugin::factory('Widget_Archive')->header = array('StructureData_Plugin', 'header');
        return _t('插件已激活，请先设置相关信息!');
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
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $defaultImage = new Typecho_Widget_Helper_Form_Element_Text(
            'defaultImage', null, null, _t('默认文章图片：')
        );
        $form->addInput(
            $defaultImage->addRule('required', _t('“默认文章图片”不能为空！'))
            ->addRule('url', _t('您输入的URL格式错误！'))
        );

        $siteLogo = new Typecho_Widget_Helper_Form_Element_Text(
            'siteLogo', null, null, _('站点 logo：')
        );
        $form->addInput(
            $siteLogo->addRule('required', _t('“站点 logo”不能为空！'))
            ->addRule('url', _t('您输入的URL格式错误！'))
        );

        $publisherType = new Typecho_Widget_Helper_Form_Element_Select(
            'publisherType',
            array(
                'Person' => _t('个人'),
                'Organization' => _t('组织')
            ),
            'Organization',
            _t('发布者（publisher 字段）类型'),
            _t('选择“个人”，将使用文章作者的名字；选择“组织”，将使用站点名字和“站点 logo”')
        );
        $form->addInput($publisherType);

        $contentType = new Typecho_Widget_Helper_Form_Element_Select(
            'contentType',
            array(
                'Article' => _t('文章'),
                'BlogPosting' => _t('博文')
            ),
            'BlogPosting',
            _t('内容类型'),
            _t('选择“文章”，将输出“Article”类型；选择“博文”，将输出“BlogPosting”类型')
        );
        $form->addInput($contentType);

        $imageURLFormat = new Typecho_Widget_Helper_Form_Element_Text(
            'imageURLFormat',
            null,
            '{image_url}',
            _t('题图链接格式'),
            _t('适用于类似七牛的图片处理样式名（后缀）；可用参数：{image_url} 图片地址')
        );
        $form->addInput(
            $imageURLFormat->addRule('required', _t('“题图链接格式”不能为空！'))
        );
    }
    
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
        // Do not output anything if post is password protected
        $db = Typecho_Db::get();
        $post_row = $db->fetchAll(
            $db->select()->from('table.contents')
            ->where('table.contents.cid = ?', $post->cid)
        );
        if(isset($post_row[0]['password'])){
            return;
        }

        // Read plugin options as fallback values
        $plugin_options = Typecho_Widget::widget('Widget_Options')->plugin('StructureData');
        $options = Typecho_Widget::widget('Widget_Options');

        $postType = $plugin_options->contentType;
        $publisherType = $plugin_options->publisherType;

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
        // Google does not allow publisher.type to be Person, but
        // schema.org does.
        if($publisherType == 'Organization'){
            $publisherName = $options->title;
            $publisherLogo = $plugin_options->siteLogo;
        }else{
            $publisherName = $authorName;
        }

        // Main image of current blog post
        // Use `image` custom field value if presented
        if($post->fields->image){
            $imageURL = $post->fields->image;
        }else{
            // Try to find the first image attachment
            $attachment = $post->attachments(1)->attachment;
            if($attachment->isImage){
                $imageURL = $attachment->url;
            }
            // Fallback to a default value configured by the user
            if(empty($imageURL)){
                $imageURL = $plugin_options->defaultImage;
            }
        }
        $imageURL = str_replace('{image_url}', $imageURL, $plugin_options->imageURLFormat);
        $imageHeight = $post->fields->imageHeight ? $post->fields->imageHeight : 200;
        $imageWidth = $post->fields->imageWidth ? $post->fields->imageWidth : 696;

        echo <<<JSONLD_HEAD
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "{$postType}",
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


        if($publisherType == 'Organization'){
            echo <<<JSONLD_PUBLISHER_ORG
    "publisher": {
        "@type": "{$publisherType}",
        "name": "{$publisherName}",
        "logo": {
            "@type": "ImageObject",
            "url": "{$publisherLogo}"
        }
    },

JSONLD_PUBLISHER_ORG;

        }else{
            echo <<<JSONLD_PUBLISHER_PERSON
    "publisher": {
        "@type": "{$publisherType}",
        "name": "{$publisherName}"
    },

JSONLD_PUBLISHER_PERSON;
        }

        echo <<<JSONLD_END
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
