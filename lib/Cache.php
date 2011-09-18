<?php
/**
 * Simple content caching class
 * 
 * Requirements: PHP5 with curl enabled
 * License: MIT
 */

class Cache 
{
    /**
     * Unique ID for content
     * 
     * @var string
     */
    protected $identifier;
    
    protected $data;
    
    protected $active = true;

    protected $cacheFile;
    
    protected $cacheDir = './';
    
    protected $expiryTime = 86400;
    
    public function setIdentifier($id)
    {
        $this->identifier = md5($id);
    }    
       
    public function setExpiry($time)
    {
        $this->expiryTime = $time;
    }
    
    public function setActive($flag)
    {   
        $this->active = (bool)$flag;
    }    
       
    public function saveData($data)
    {
        $this->data = serialize($data);
        
        $total = 0;
        
        if(!$this->active)
            return;
        $this->setCacheFileName();        
        if($this->data != null)
        {
            $total = file_put_contents($this->cacheFile, $this->data, LOCK_EX);
            @touch($this->cacheFile, time() + $this->expiryTime);           
        }        
        return $total;
    }
    
    public function isCached()
    {
        $this->setCacheFileName();              
         
        if($this->active AND file_exists($this->cacheFile) AND filemtime($this->cacheFile) > time())
            return true;
        else
        {
            $this->removeCacheFile();
            return false;
        }
    }

    protected function removeCacheFile()
    {
        @unlink($this->cacheFile);
    }
    
    public function fetchData()
    {
        return unserialize(file_get_contents($this->cacheFile));        
    }
      
    public function setCacheDir($dir)
    {
       $this->cacheDir = $dir;       
    }
    
    protected function setCacheFileName()
    {
        if(!file_exists($this->cacheDir) OR !is_writeable($this->cacheDir))
            throw new Exception('Cache dir: '.$this->cacheDir.' is not writeable or does not exist');
        elseif($this->identifier == null)
            throw new Exception('Cache identifer not set');
        else
            $this->cacheFile = $this->cacheDir.DIRECTORY_SEPARATOR.$this->identifier;
    }
}

?>