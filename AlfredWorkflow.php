<?php
# https://github.com/xtremforce/AlfredWorkflow

class AlfredWorkflow
{
    private $items = [];

    public function getItems(){
        return $this->items;
    }

    public function setItems($items){
        $this->items = $items;
    }

    public function reset(){
        $this->items = [];
    }

    public function reverse(){
        $this->items = array_reverse($this->items);
    }

    // 计算字符串的渲染长度，粗略地让中文算 2，英文算 1
    function getTextRenderWidth($text){
        $chars = mb_str_split($text, 1, 'UTF-8');
        $width = 0;

        foreach($chars as $char){
            if(preg_match('/^[\x{4e00}-\x{9fef}\x{3000}-\x{303f}]$/u', $char)){
                $width = $width+2;
            }else{
                $width = $width+1;
            }
            /* if(ctype_alpha($char) || is_numeric($char) || $char=" " || preg_match('/^[\p{Latin}]+$/u', $char)){
                $width++;
            }else{
                $width = $width+2;
            } */
        }
        return $width;
    }

    function showLongText($text,$arg="")
    {
        $maxLineWidth = 60;
        $this->reset();
        if(empty($arg)){
            $arg = $text;
        }

        /* if(mb_strlen($text) <= 8*$maxLineWidth){
            $viewMode = 'OnlyTitles';
        }else{
            $viewMode = 'TitlesAndSubTitiles';
        } */

        $str = $this->splitString($text, $maxLineWidth);
        $maxCount = 9;
        for($i = 0; $i < count($str); $i++){
            if($i >= $maxCount-1){
                break;
            }
            $this->addItem('', $str[$i], $str[$i], "", "");
        } 
/* 
        if($viewMode=='OnlyTitles'){
            for($i = 0; $i < count($str); $i++){
                if($i >= $maxCount){
                    break;
                }
                $this->addItem('', $str[$i], $str[$i], "", "");
            } 
        }else{
            $groupedArray = array_chunk($str, 2);
            for($i = 0; $i < count($groupedArray); $i++){
                if($i >= $maxCount){
                    break;
                }
                if($i>0){
                    $arg="";
                }
                $this->addItem('', $arg, $groupedArray[$i][0]??"", $groupedArray[$i][1]??"", "", "");
            }
        } */

        $this->output();
        exit();die();
    }

    //按照中英文渲染的长度限制分割字符串。并且防止英文单词在中间被截断
    function splitString($str, $length) {
        $chunks = [];
        $chunk = "";
        $i = 0;      

        while ($i <= mb_strlen($str)) {
            $strI = mb_substr($str, $i, 1, 'UTF-8');

            if(preg_match('/^[\x{4e00}-\x{9fef}\x{3000}-\x{303f}]$/u', $strI)){
            // if (($s[$i] >= '\u4e00' && $s[$i] <= '\u9fef') || ($s[$i] >= '\u3000' && $s[$i] <= '\u303f')) { // 当前字符是中文字符或标点
                if ($this->getTextRenderWidth($chunk . $strI) <= $length) { // 如果加上这个字符后长度没有超过限制
                    $chunk .= $strI;
                } else { // 如果加上这个字符后长度超过了限制
                    array_push($chunks, $chunk);
                    $chunk = $strI;
                }
            } elseif (ctype_alpha($strI)) { // 当前字符是英文字符
                preg_match('/\b\w+/', mb_substr($str, $i), $matches); // 提取完整的英文单词
                $word = $matches[0];
                if ($this->getTextRenderWidth($chunk . $word) <= $length) { // 如果加上这个单词后长度没有超过限制
                    $chunk .= $word;
                    $i += mb_strlen($word) - 1; // i 跳过这个单词的剩余部分
                } else { // 如果加上这个单词后长度超过了限制
                    array_push($chunks, $chunk);
                    $chunk = $word;
                    $i += mb_strlen($word) - 1;
                }
            } else { // 当前字符是其他字符，包括英文标点等
                if ($this->getTextRenderWidth($chunk . $strI) <= $length) { // 如果加上这个字符后长度没有超过限制
                    $chunk .= $strI;
                } else { // 如果加上这个字符后长度超过了限制
                    array_push($chunks, $chunk);
                    $chunk = $strI;
                }
            }
            if ($this->getTextRenderWidth($chunk) == $length) { // 如果当前行长度已经达到了限制
                array_push($chunks, $chunk);
                $chunk = "";
            }
            $i++;
        }
    
        if ($chunk) { // 把最后一行加到结果中
            array_push($chunks, $chunk);
        }
        return $chunks;
    }
    
  /*   function addLongText($text) {
        $this->reset();
        echo "ss";
        $textArr = $this->splitString($text, 65);
        var_dump($textArr);
        exit();
        foreach ($textArr as $item) {
            $this->addItem('', $item, "", $item);
        }
    }
     */


    public function addItem($uid, $arg, $title, $subtitle="", $icon='', $mods=[], $valid=true, $autocomplete='',$quicklookurl='', $type='default' )
    {
        $item = [];
        if(!empty($uid && $uid != '')){
            $item['uid']=$uid;
        }

        $item = [
            'title' => $title,
            'subtitle' => $subtitle,
            'arg' => $arg,
            'icon' => $icon,
        ];

        if($type != 'default'){
            $item['type']=$type;
        }

        if(!$valid || strtolower($valid)=='false' || strtolower($valid)=='no'){
            $item['valid']=false;
        }
        
        if(!empty($autocomplete)){
            $item['autocomplete']=$autocomplete;
        }

        if(!empty($quicklookurl)){
            $item['quicklookurl']=$quicklookurl;
        }

        //用户按下修饰键，比如 Shift、Ctrl 时，可以输出不同的值
        if(!empty($mods) && is_array($mods) && count($mods)>0){
            $item['mods']=$mods;
        }

        $this->items[] = $item;
    }

    public function showMessage($title,$subtitle="",$arg="",$icon=""){
        $this->reset();
        if(empty($arg)){
            $arg = $title;
        }
        $this->addItem("", $arg, $title, $subtitle, $icon);
        $this->output();
        exit();
        die();
    }

	/* 
	private function arrayToXml($array, &$xml) {
		foreach($array as $key => $value) {
			if(is_array($value)) {
				$node = $xml->addChild($key);
				$this->arrayToXml($value, $node);
			} else {
				$xml->addChild($key, $value);
			}
		}
	}

	//如果使用 toXML，则 MODS 数组的格式需要改变一下，否则会错误
	public function toXml(){
		$xml = new SimpleXMLElement('<items></items>');
		foreach ($this->items as $item) {
			$child = $xml->addChild('item');
			$this->arrayToXml($item, $child);
		}
		return $xml->asXML();
	} */


    public function toJson()
    {
        $data = ['items' => $this->items];
        return json_encode($data);
    }

    public function output(){
        echo $this->toJson();
    }
}

