<?php
namespace GDO\ImportGWF3\Method;
use GDO\Core\Logger;
use GDO\ImportGWF3\MethodImport;
use GDO\User\GDO_User;
use GDO\Date\Time;
use GDO\User\GDT_Gender;
final class ImportUsers extends MethodImport
{
    public function run()
    {
        Logger::logCron("Importing Users");
        $query = "SELECT * FROM {$this->prefix}user";
        $result = $this->gwfdb()->queryRead($query);
        $this->gdodb();

        # Bulk copy
        $fields = GDO_User::table()->gdoColumns();
        $data = [];
        while ($row = mysqli_fetch_assoc($result))
        {
            $data[] = $this->userData($row);
        }
        mysqli_free_result($result);
        $usercount = count($data);
        GDO_User::bulkReplace($fields, $data);
        Logger::logCron("Imported $usercount Users");
    }

    private function userData(array $row)
    {
        return array(
            $row['user_id'],
            $this->userType($row),
            $row['user_name'],
            null,
            null,
            $row['user_email'],
            round($row['user_level']),
            round($row['user_credits']),
            ($row['user_options']&0x1000)?'text':'html',
            $this->userGender($row['user_gender']),
            $this->gwfdate($row['user_birthdate']),
            $this->gwfcountry($row['user_countryid']),
            $this->gwflanguage($row['user_langid'], 'en'),
            null,
            ($row['user_options']&0x02)?Time::getDate():null,
            $this->gwfdate($row['user_lastactivity']),
            $this->gwfdate($row['user_regdate']),
            $this->gwfip($row['user_regip']),
        );
    }
        
    private function userType(array $row)
    {
        if ($row['user_options']&0x01)
        {
            return GDO_User::BOT;
        }
        elseif ($row['user_options']&0x80)
        {
            return GDO_User::GUEST;
        }
        else
        {
            return GDO_User::MEMBER;
        }
    }
    
    private function userGender(string $gender)
    {
        switch($gender)
        {
            case GDT_Gender::MALE: case GDT_Gender::FEMALE: return $gender;
            default: return GDT_Gender::NONE;
        }
    }
    
}
