<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
define("TOKEN", "zilong");

class WeiXin extends CI_Controller {
	 public function __construct()
    {
        parent::__construct();
        $this->load->model('Common_model');
        
    }
	public function index(){
        if (!isset($_GET['echostr'])) {
            $this->responseMsg();
        }else{
            $this->valid();
        }

    }
    
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature){
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            //消息类型分离
            switch ($RX_TYPE)
            {
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
                default:
                    $result = "不支持的事件类型".$RX_TYPE;
                    break;
            }
            echo $result;
        }else {
            echo "";
            exit;
        }
    }
     //接收文本消息
     private function receiveText($object)
     {
         $keyword = trim($object->Content);
         //多客服人工回复模式
         if (strstr($keyword, "帮助") || strstr($keyword, "help") || strstr($keyword, "?")){
             $result = $this->transmitService($object);
         }
         //自动回复模式
         else{
                $content = array();
                if (is_numeric($keyword)){
                    if(11==strlen($keyword))
                    {
                        $sql="SELECT r.regionName,p.* FROM i_plc AS p
                        LEFT JOIN i_region AS r ON r.regionId=p.regoinId
                        WHERE serialNum='$keyword'";
                        $obj=$this->Common_model->queryrow($sql);
                        $content[] = array("Title"=>"PLC温度信息查询",  "Description"=>"
                        PLC识别码：$obj->serialNum
                        PLC名称：$obj->plcName
                        在线状态：$obj->online
                        更新时间:$obj->collectTime
                        管壁内温度：$obj->inTemperature
                        管壁外温度：$obj->exTemperature
                        环境温度：$obj->enTemperature
                        区域：$obj->regionName
                        地址：$obj->addr"
                        , "PicUrl"=>"https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3498618166,2076407864&fm=27&gp=0.jpg",
                         "Url" =>"http://m.cnblogs.com/?u=txw1958");
                        
                    }else
                    {
                        $sql="SELECT r.regionName,p.* FROM i_plc AS p
                        LEFT JOIN i_region AS r ON r.regionId=p.regoinId
                        WHERE serialNum like '%$keyword%' LIMIT 0,6";
                        $objs=$this->Common_model->getGrid($sql);
                        $content[] = array("Title"=>"PLC身份识别码信息查询", "Description"=>"", 
                        "PicUrl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", 
                        "Url" =>"http://m.cnblogs.com/?u=txw1958");
                        foreach($objs as $obj)
                        {
                            $content[] = array("Title"=>"
                            $obj->serialNum",
                            "Description"=>"",
                            "PicUrl"=>"https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3498618166,2076407864&fm=27&gp=0.jpg",
                            "Url" =>"http://m.cnblogs.com/?u=txw1958");
                        }
                    }
             }else{
                 $content = array();
                 $content[] = array("Title"=>"多图文1标题", "Description"=>"", 
                 "PicUrl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", 
                 "Url" =>"http://m.cnblogs.com/?u=txw1958");
                 $content[] = array("Title"=>"多图文2标题", "Description"=>"", 
                 "PicUrl"=>"http://d.hiphotos.bdimg.com/wisegame/pic/item/f3529822720e0cf3ac9f1ada0846f21fbe09aaa3.jpg",
                  "Url" =>"http://m.cnblogs.com/?u=txw1958");
                 $content[] = array("Title"=>"多图文3标题", "Description"=>"", 
                 "PicUrl"=>"http://g.hiphotos.bdimg.com/wisegame/pic/item/18cb0a46f21fbe090d338acc6a600c338644adfd.jpg", 
                 "Url" =>"http://m.cnblogs.com/?u=txw1958");
             }
             
             if(is_array($content)){
                 if (isset($content[0]['PicUrl'])){
                     $result = $this->transmitNews($object, $content);
                 }else if (isset($content['MusicUrl'])){
                     $result = $this->transmitMusic($object, $content);
                 }
             }else{
                 $result = $this->transmitText($object, $content);
             }
         }
 
         return $result;
     }
     //回复文本消息
     private function transmitText($object, $content)
     {
         
        $xmlTpl = "<xml>
 <ToUserName><![CDATA[%s]]></ToUserName>
 <FromUserName><![CDATA[%s]]></FromUserName>
 <CreateTime>%s</CreateTime>
 <MsgType><![CDATA[text]]></MsgType>
 <Content><![CDATA[%s]]></Content>
 </xml>";
         $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
         return $result;
     }

     //回复图文消息
     private function transmitNews($object, $newsArray)
     {
         if(!is_array($newsArray)){
             return;
         }
         $itemTpl = "    <item>
         <Title><![CDATA[%s]]></Title>
         <Description><![CDATA[%s]]></Description>
         <PicUrl><![CDATA[%s]]></PicUrl>
         <Url><![CDATA[%s]]></Url>
     </item>
 ";
         $item_str = "";
         foreach ($newsArray as $item){
             $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
         }
         $xmlTpl = "<xml>
 <ToUserName><![CDATA[%s]]></ToUserName>
 <FromUserName><![CDATA[%s]]></FromUserName>
 <CreateTime>%s</CreateTime>
 <MsgType><![CDATA[news]]></MsgType>
 <ArticleCount>%s</ArticleCount>
 <Articles>
 $item_str</Articles>
 </xml>";
 
         $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
         return $result;
     }

     //回复多客服消息
     private function transmitService($object)
     {
         $xmlTpl = "<xml>
 <ToUserName><![CDATA[%s]]></ToUserName>
 <FromUserName><![CDATA[%s]]></FromUserName>
 <CreateTime>%s</CreateTime>
 <MsgType><![CDATA[transfer_customer_service]]></MsgType>
 </xml>";
         $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
         return $result;
     }
 
     //日志记录
     private function logger($log_content)
     {
         return;
         if(isset($_SERVER['HTTP_APPNAME'])){   //SAE
             sae_set_display_errors(false);
             sae_debug($log_content);
             sae_set_display_errors(true);
         }else if($_SERVER['REMOTE_ADDR'] != "127.0.0.1"){ //LOCAL
             $max_size = 10000;
             $log_filename = "log.xml";
             if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
             file_put_contents($log_filename, date('H:i:s')." ".$log_content."\r\n", FILE_APPEND);
         }
     }
}