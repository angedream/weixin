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

        //$this->load->view('offlinePlc', $this->middleware);
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
            $this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
             
            //消息类型分离
            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
                default:
                    $result = "不支持的事件类型".$RX_TYPE;
                    break;
            }
            $this->logger("T ".$result);
            echo $result;
        }else {
            echo "";
            exit;
        }
    }

     //接收事件消息
     private function receiveEvent($object)
     {
         $content = "";
         switch ($object->Event)
         {
             case "subscribe":
                 $content = "欢迎关注方倍工作室 ";
                 $content .= (!empty($object->EventKey))?("\n来自二维码场景 ".str_replace("qrscene_","",$object->EventKey)):"";
                 break;
             case "unsubscribe":
                 $content = "取消关注";
                 break;
             case "SCAN":
                 $content = "扫描场景 ".$object->EventKey;
                 break;
             case "CLICK":
                 switch ($object->EventKey)
                 {
                     case "COMPANY":
                         $content = array();
                         $content[] = array("Title"=>"多图文1标题", "Description"=>"", "PicUrl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
                         break;
                     default:
                         $content = "点击菜单：".$object->EventKey;
                         break;
                 }
                 break;
             case "LOCATION":
                 $content = "上传位置：纬度 ".$object->Latitude.";经度 ".$object->Longitude;
                 break;
             case "VIEW":
                 $content = "跳转链接 ".$object->EventKey;
                 break;
             case "MASSSENDJOBFINISH":
                 $content = "消息ID：".$object->MsgID."，结果：".$object->Status."，粉丝数：".$object->TotalCount."，过滤：".$object->FilterCount."，发送成功：".$object->SentCount."，发送失败：".$object->ErrorCount;
                 break;
             default:
                 $content = "receive a new event: ".$object->Event;
                 break;
         }
         if(is_array($content)){
             if (isset($content[0])){
                 $result = $this->transmitNews($object, $content);
             }else if (isset($content['MusicUrl'])){
                 $result = $this->transmitMusic($object, $content);
             }
         }else{
             $result = $this->transmitText($object, $content);
         }
 
         return $result;
     }
 
     //接收文本消息
     private function receiveText($object)
     {
         $keyword = trim($object->Content);
         //多客服人工回复模式
         if (strstr($keyword, "您好") || strstr($keyword, "你好") || strstr($keyword, "在吗")){
             $result = $this->transmitService($object);
         }
         //自动回复模式
         else{
             if (strstr($keyword, "文本")){
                 $content = "这是个文本消息";
             }else if (strstr($keyword, "单图文")){
                 $content = array();
                 $content[] = array("Title"=>"PLC温度信息查询",  "Description"=>"
                 PLC识别码：13512485472
                 PLC名称：王顶堤金管理
                 在线状态：离线
                 更新时间:2017-10-17 14:36:28
                 管壁内温度：12.2℃
                 管壁外温度：12℃
                 环境温度：39℃
                 区域：南开区
                 地址：王顶堤金冠里6-1门楼顶"
                 , "PicUrl"=>"https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3498618166,2076407864&fm=27&gp=0.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
             }else if (strstr($keyword, "图文") || strstr($keyword, "多图文")){
                 $content = array();
                 $content[] = array("Title"=>"多图文1标题", "Description"=>"", "PicUrl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
                 $content[] = array("Title"=>"多图文2标题", "Description"=>"", "PicUrl"=>"http://d.hiphotos.bdimg.com/wisegame/pic/item/f3529822720e0cf3ac9f1ada0846f21fbe09aaa3.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
                 $content[] = array("Title"=>"多图文3标题", "Description"=>"", "PicUrl"=>"http://g.hiphotos.bdimg.com/wisegame/pic/item/18cb0a46f21fbe090d338acc6a600c338644adfd.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
             }else if (strstr($keyword, "音乐")){
                 $content = array();
                 $content = array("Title"=>"最炫民族风", "Description"=>"歌手：凤凰传奇", "MusicUrl"=>"http://121.199.4.61/music/zxmzf.mp3", "HQMusicUrl"=>"http://121.199.4.61/music/zxmzf.mp3");
             }else{
                 $content = date("Y-m-d H:i:s",time())."\n".$object->FromUserName."\n技术支持 方倍工作室";
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
         $content1="
         PLC识别码：13512485472\n
         PLC名称：王顶堤金管理\n
         在线状态：离线
         更新时间:2017-10-17 14:36:28\n
         管壁内温度：12.2℃\n
         管壁外温度：12℃\n
         环境温度：39℃\n
         区域：南开区\n
         地址：王顶堤金冠里6-1门楼顶\n
         ";
         $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
         return $result;
     }
 
     //回复图片消息
     private function transmitImage($object, $imageArray)
     {
         $itemTpl = "<Image>
     <MediaId><![CDATA[%s]]></MediaId>
 </Image>";
 
         $item_str = sprintf($itemTpl, $imageArray['MediaId']);
 
         $xmlTpl = "<xml>
 <ToUserName><![CDATA[%s]]></ToUserName>
 <FromUserName><![CDATA[%s]]></FromUserName>
 <CreateTime>%s</CreateTime>
 <MsgType><![CDATA[image]]></MsgType>
 $item_str
 </xml>";
 
         $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
         return $result;
     }
 
     //回复语音消息
     private function transmitVoice($object, $voiceArray)
     {
         $itemTpl = "<Voice>
     <MediaId><![CDATA[%s]]></MediaId>
 </Voice>";
 
         $item_str = sprintf($itemTpl, $voiceArray['MediaId']);
 
         $xmlTpl = "<xml>
 <ToUserName><![CDATA[%s]]></ToUserName>
 <FromUserName><![CDATA[%s]]></FromUserName>
 <CreateTime>%s</CreateTime>
 <MsgType><![CDATA[voice]]></MsgType>
 $item_str
 </xml>";
 
         $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
         return $result;
     }
 
     //回复视频消息
     private function transmitVideo($object, $videoArray)
     {
         $itemTpl = "<Video>
     <MediaId><![CDATA[%s]]></MediaId>
     <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
     <Title><![CDATA[%s]]></Title>
     <Description><![CDATA[%s]]></Description>
 </Video>";
 
         $item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);
 
         $xmlTpl = "<xml>
 <ToUserName><![CDATA[%s]]></ToUserName>
 <FromUserName><![CDATA[%s]]></FromUserName>
 <CreateTime>%s</CreateTime>
 <MsgType><![CDATA[video]]></MsgType>
 $item_str
 </xml>";
 
         $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
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
 
     //回复音乐消息
     private function transmitMusic($object, $musicArray)
     {
         $itemTpl = "<Music>
     <Title><![CDATA[%s]]></Title>
     <Description><![CDATA[%s]]></Description>
     <MusicUrl><![CDATA[%s]]></MusicUrl>
     <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
 </Music>";
 
         $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);
 
         $xmlTpl = "<xml>
 <ToUserName><![CDATA[%s]]></ToUserName>
 <FromUserName><![CDATA[%s]]></FromUserName>
 <CreateTime>%s</CreateTime>
 <MsgType><![CDATA[music]]></MsgType>
 $item_str
 </xml>";
 
         $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
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





    public function getOrder($id)
    {
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
        $offset = ($page-1)*$rows;
        $result = array();

        $sqlTotal="SELECT COUNT(*) as num FROM i_person AS p
                LEFT JOIN i_personregion AS pr ON p.personId=pr.personId
                LEFT JOIN i_region AS r ON r.regionId=pr.regionId
                LEFT JOIN i_plc AS pc ON pc.regoinId=pr.regionId
                WHERE p.personId=$id and pc.`online`=0";
        $sql="SELECT pc.*,r.regionName,pc.plcId AS id FROM i_person AS p
                LEFT JOIN i_personregion AS pr ON p.personId=pr.personId
                LEFT JOIN i_region AS r ON r.regionId=pr.regionId
                LEFT JOIN i_plc AS pc ON pc.regoinId=pr.regionId
                WHERE p.personId=$id and pc.`online`=0 ORDER BY pc.regoinId 
                                LIMIT $offset,$rows";
        $total=$this->Common_model->queryrow($sqlTotal);                          
        $result["total"] = $total->num;
        $result["rows"] = $this->Common_model->getGrid($sql);
        echo json_encode($result,JSON_UNESCAPED_UNICODE);
        
    }
    
    //手机专用
    public function getRegionName($id)
    {
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
        $offset = ($page-1)*$rows;
        $result = array();

        $sqlTotal="SELECT COUNT(*) AS num FROM i_person AS p
                    LEFT JOIN i_personregion AS pr ON p.personId=pr.personId
                    LEFT JOIN i_region AS r ON r.regionId=pr.regionId
                    WHERE p.personId=$id";
        $sql="SELECT r.regionName,r.regionId,COUNT(*) AS num,0 as totalNum,p.personId FROM i_person AS p
                    LEFT JOIN i_personregion AS pr ON p.personId=pr.personId
                    LEFT JOIN i_region AS r ON r.regionId=pr.regionId
                    LEFT JOIN i_plc AS pc ON pc.regoinId=pr.regionId
                    WHERE p.personId=$id and pc.`online`=0                         
                    GROUP BY pc.regoinId
                    LIMIT $offset,$rows;";
        $total=$this->Common_model->queryrow($sqlTotal);                          
        $result["total"] = $total->num;
        $result["rows"] = $this->Common_model->getGrid2($sql);
        echo json_encode($result,JSON_UNESCAPED_UNICODE);
        
    }
    //手机专用
    public function getPlcForRegionId($personId,$regionId)
    {
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
        $offset = ($page-1)*$rows;
        $result = array();

        $sqlTotal="SELECT COUNT(*) as num FROM i_person AS p
                LEFT JOIN i_personregion AS pr ON p.personId=pr.personId
                LEFT JOIN i_region AS r ON r.regionId=pr.regionId
                LEFT JOIN i_plc AS pc ON pc.regoinId=pr.regionId
                WHERE p.personId=$personId and pc.`online`=0 and pc.regoinId=$regionId";
        $sql="SELECT pc.*,r.regionName,pc.plcId AS id FROM i_person AS p
                LEFT JOIN i_personregion AS pr ON p.personId=pr.personId
                LEFT JOIN i_region AS r ON r.regionId=pr.regionId
                LEFT JOIN i_plc AS pc ON pc.regoinId=pr.regionId
                WHERE p.personId=$personId and pc.`online`=0 and pc.regoinId=$regionId 
                                LIMIT $offset,$rows";
        $total=$this->Common_model->queryrow($sqlTotal);                          
        $result["total"] = $total->num;
        $result["rows"] = $this->Common_model->getGrid($sql);
        echo json_encode($result,JSON_UNESCAPED_UNICODE);
        
    }
 
}