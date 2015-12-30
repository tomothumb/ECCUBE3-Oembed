<?php
namespace Plugin\Oembed;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Filesystem\Filesystem;

require_once(__DIR__."/vendor/autoload.php");
use Embed\Embed;
use Wa72\HtmlPageDom\HtmlPageCrawler;


class OembedEvent
{

    /** @var  \Eccube\Application $app */
    private $app;

    public function __construct($app)
    {
        $this->app = $app;

    }


    /**
     *
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderOembedAdminProductDetailEditBefore(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();


        $parts = $this->app->renderView('Oembed/Resource/template/admin/embed-modal.twig', array(
        ));

//        $crawler = new Crawler($response->getContent());
        $crawler = new HtmlPageCrawler($response->getContent());

        $crawler->filter('#admin_product_free_area')
            ->before($parts);

//        $html = $this->getHtml($crawler);
        $html = $crawler->html();
        $html = html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
        $response->setContent($html);

        $event->setResponse($response);
    }
//        \Doctrine\Common\Util\Debug::dump($p->html());


    /**
     *
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderOembedProductDetailBefore(FilterResponseEvent $event)
    {

        $request = $event->getRequest();
        $response = $event->getResponse();

        $crawler = new Crawler($response->getContent());

        $html = $this->getHtml($crawler);
        try {
            $freearea = $crawler->filter('.freearea');
            $oldHtml = $freearea->html();
            $newHtml = $this->do_embed($oldHtml);
            $html = str_replace($oldHtml, $newHtml, $html);

        } catch (\InvalidArgumentException $e) {
        }

        $html = html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
        $response->setContent($html);


        $event->setResponse($response);
    }


    /**
     * 解析用HTMLを取得
     *
     * @param Crawler $crawler
     * @return string
     */
    private function getHtml(Crawler $crawler)
    {
        $html = '';
        foreach ($crawler as $domElement) {
            $domElement->ownerDocument->formatOutput = true;
            $html .= $domElement->ownerDocument->saveHTML();
        }
        return html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }



    function do_embed( $html ) {

        preg_match_all( '/(\[embed\])(.*)(\[\/embed\])/', $html, $matches );
        if(count($matches[2]) < 1){
            return false;
        }
        $convart = array(
            "src" => $matches[0],
            "urls" => $matches[2],
            "code" => array()
        );

        foreach ($convart["urls"] as $index => $value){
            $convart["code"][$index] = $this->get_embed($value);
        }

        foreach ($convart["src"] as $index => $value){
            $html = str_replace($value, $convart["code"][$index], $html);
        }

        return $html;
    }


    /**
     * @param $url
     * @return bool
     * @throws \Embed\Exceptions\InvalidUrlException
     */
    private function get_embed($url){

        $obj = \Embed\Embed::create($url);
        if($obj->code){
            return $obj->code;
        }elseif($obj->image){
            return $obj->image;
        }
        return false;
    }


}
