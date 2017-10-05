<?php
namespace GDO\ImportGWF3;
use GDO\Core\Method;
use GDO\DB\Database;
use GDO\Core\GDOException;
use GDO\Date\Time;
use GDO\Country\CountryData;
use GDO\Language\LanguageData;
use GDO\Language\GDO_Language;
use GDO\Country\GDO_Country;

abstract class MethodImport extends Method
{
    public abstract function run();
    
    public function execute()
    {
        if (!$this->connectGWFDB())
        {
            return $this->error('err_connect_gwf3_db')->add($this->renderPage());
        }
        return $this->run();
    }
    
    public function import(Database $gwfdb)
    {
        $this->gdodb = Database::instance();
        $this->gwfdb = $gwfdb;
        $this->prefix = Module_ImportGWF3::instance()->cfgDBPrefix();
        return $this->run();
    }
    
    #################
    ### DB-Switch ###
    #################
    private $gdodb;
    private $gwfdb;
    protected $prefix;
    public function gdodb() { Database::$INSTANCE = $this->gdodb; return $this->gdodb; }
    public function gwfdb() { Database::$INSTANCE = $this->gwfdb; return $this->gwfdb; }
    public function connectGWFDB()
    {
        # Remember GDO
        $this->gdodb = Database::$INSTANCE;
        # Create GWF3
        $module = Module_ImportGWF3::instance();
        $this->gwfdb = new Database($module->cfgDBHost(), $module->cfgDBUser(), $module->cfgDBPass(), $module->cfgDBName());
        $result = !!$this->gwfdb->connect();
        $this->prefix = $module->cfgDBPrefix();
        # Restore GDO
        $this->gdodb();
        return $result;
    }
    
    ###############
    ### Convert ###
    ###############
    /**
     * Convert a gwf date to a gdo date.
     * @param string $gwfdate
     * @throws GDOException
     * @return string
     */
    public function gwfdate(string $gwfdate=null)
    {
        $sec = $min = $hour = $day = $mon = $year = 0;
        switch (strlen($gwfdate))
        {
            case 14: $sec = intval(substr($gwfdate, 12, 2), 10);
            case 12: $min = intval(substr($gwfdate, 10, 2), 10);
            case 10: $hour = intval(substr($gwfdate, 8, 2), 10);
            case 8: $day = intval(substr($gwfdate, 6, 2), 10);
            case 6: $mon = intval(substr($gwfdate, 4, 2), 10);
            case 4: $year = intval(substr($gwfdate, 0, 4), 10);
                break;
            case 1: case 0: return null;
            default: throw new GDOException('invalid gwf date: '.$gwfdate);
        }
        return Time::getDate(mktime($hour, $min, $sec, $mon, $day, $year));
    }
    
    public function gwfcountry($id)
    {
        static $mapping;
        if (!$mapping)
        {
            $mapping = $this->gwfCountryMapping();
        }
        $data = CountryData::getCountries();
        return isset($mapping[$id]) ? $mapping[$id] : null;
    }

    private function gwfCountryMapping()
    {
        $mapping = [];
        $result = $this->gwfdb()->queryRead("SELECT * FROM {$this->prefix}country");
        $this->gdodb();
        while ($row = mysqli_fetch_array($result))
        {
            if (GDO_Country::getById($row['country_tld']))
            {
                $mapping[$row['country_id']] = $row['country_tld'];
            }
        }
        mysqli_free_result($result);
        return $mapping;
    }

    public function gwflanguage($id, $default=null)
    {
        static $mapping;
        if (!$mapping)
        {
            $mapping = $this->gwfLanguageMapping();
        }
        return isset($mapping[$id]) ? $mapping[$id] : $default;
    }
    
    private function gwfLanguageMapping()
    {
        $mapping = [];
        $result = $this->gwfdb()->queryRead("SELECT * FROM {$this->prefix}language");
        $this->gdodb();
        while ($row = mysqli_fetch_array($result))
        {
            if (GDO_Language::getById($row['lang_iso']))
            {
                $mapping[$row['lang_id']] = $row['lang_iso'];
            }
        }
        mysqli_free_result($result);
        return $mapping;
    }
    
    public function gwfip($ip)
    {
        return empty($ip) ? null : inet_ntop($ip);
    }
}
