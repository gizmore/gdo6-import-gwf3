<?php
namespace GDO\ImportGWF3\Method;
use GDO\ImportGWF3\MethodImport;
use GDO\Core\Logger;
use GDO\User\GDO_User;
use GDO\Avatar\GDO_UserAvatar;
/**
 * @author gizmore
 */
final class ImportAvatars extends MethodImport
{
    public function run()
    {
    	Logger::logCron("Importing Avatars");

    	$query = "SELECT * FROM {$this->prefix}user WHERE user_avatar_v>0 AND NOT (user_options&1)";
    	$result = $this->gwfdb()->queryRead($query);
    	$this->gdodb();
    	$count = 0;
    	while ($row = mysqli_fetch_assoc($result))
    	{
    		$count += $this->convertAvatar($row);
    	}
    	mysqli_free_result($result);
    	
    	Logger::logCron("Imported $count avatars.");
    }
    
    private function convertAvatar(array $row)
    {
    	$path = $this->gwfpath . '/avatar/' . $row['user_id'];
    	if (is_file($path))
    	{
	    	$user = GDO_User::getById($row['user_id']);
	    	GDO_UserAvatar::createAvatarFromString($user, "gwf3import", file_get_contents($path));
	    	return 1;
    	}
    	return 0;
    }
    
}
