<?php

// Body
class MdBody extends Body
{
    var $id;
    var $pukiwiki;
    var $is_markdown;
    var $text;

    function MdBody($id, $lines)
    {
	$this->__construct($id, $lines);
    }
    
    function __construct($id, $lines)
    {
	$this->id          = $id;
        $this->is_markdown = preg_grep('/^\#notemd/', $lines) ? true : false;
	parent::__construct($id);
    }

    function parse(& $lines)
    {
        if (! $this->is_markdown) {
            // Pukiwiki記法
            $this->pukiwiki = new Body($id);
	    $this->pukiwiki->parse($lines);
        } else {
	    // Markdown記法
            foreach ( $lines as &$line ) {
	        $matches = array();
	        
	        $line = preg_replace('/(\#author\(.*\)|\#notemd|\#freeze)/', '', $line); // #author,#notemd,#freezeはMarkdown Parserに渡さない
	        if ( preg_match('/^#([a-zA-Z0-9_]+)(\\(([^\\)\\n]*)?\\))?/', $line, $matches) ) {
	            $plugin = $matches[1];
	            if ( exist_plugin_convert($plugin) ) {
	                $name = 'plugin_' . $matches[1] . '_convert';
	                $params = array();
	                if ( isset($matches[3]) ) {
	                    $params = explode(',', $matches[3]);
	                }
	                $line = call_user_func_array($name, $params);
	            } else {
	                $line = "plugin ${plugin} failed.";
	            }
	        } else if (preg_match('/^\!(\[.*\])(\((https?\:\/\/[\-_\.\!\~\*\'\(\)a-zA-Z0-9\;\/\?\:\@\&\=\+\$\,\%\#]+\.)?(jpe?g|png|gif|webp)\))/u', $line, $matchimg)) {
	            // Markdown記法の画像の場合はmake_linkに渡さない
	        } else {
	            // $line = preg_replace('/\[(.*?)\]\((https?\:\/\/[\-_\.\!\~\*\'\(\)a-zA-Z0-9\;\/\?\:\@\&\=\+\$\,\%\#]+)( )?(\".*\")?\)/', "[[$1>$2]]", $line); // Markdown式リンクをPukiwiki式リンクに変換
	            $line = preg_replace('/\[\[(.+)[\:\>](https?\:\/\/[\-_\.\!\~\*\'\(\)a-zA-Z0-9\;\/\?\:\@\&\=\+\$\,\%\#]+)\]\]/', "[$1]($2)", $line); // Pukiwiki式リンクをMarkdown式リンクに変換
	            $line = preg_replace('/\[\#[a-zA-Z0-9]{8}\]$/', "", $line); // Pukiwiki式アンカーを非表示に
	            $line = make_link($line);
	            // ファイル読み込んだ場合に改行コードが末尾に付いていることがあるので削除
	            // 空白は削除しちゃだめなのでrtrim()は使ってはいけない
	        }
	        $line = str_replace(array("\r\n","\n","\r"), "", $line);
	    }
	    unset($line);

            $lines = implode("\n", $lines);
	    
	    $parsedown = new \ParsedownToc(); //Parsedown→ParsedownExtraに変更しても良い
	    $this->text = $parsedown->setMarkupEscaped(false)
                                    ->setSafeMode(false) // safemode
                                    ->setBreaksEnabled(true) // enables automatic line breaks
                                    ->text($lines);
        }
    }

    function toString()
    {
        if (! $this->is_markdown) {
            return $this->pukiwiki->toString();
        } else {
            return $this->text;
        }
    }

}

