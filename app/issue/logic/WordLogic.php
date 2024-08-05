<?php

namespace app\issue\logic;

class WordLogic
{


    public function start()
    {
        ob_start();
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40">';
    }

    public function save($path)
    {
        echo "</html>";
        $data = ob_get_contents();
        ob_end_clean();
        $this -> wirtefile($path, $data);
    }

    public function wirtefile($fn, $data)
    {
        $fp = fopen($fn, "wb");
        fwrite($fp, $data);
        fclose($fp);
    }

    /**
     * @导出文件
     *
     * @author: zsl
     * @since: 2020/7/20 13:49
     */
    public function export($html = '', $fileName = 'export.doc')
    {
        $this -> start();
        echo $html;
        $this -> save($fileName);
        // 文件的类型
        header('Content-type: application/word');
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        readfile($fileName);
        ob_flush();
        flush();
        //删除文件
        unlink($fileName);
    }


}