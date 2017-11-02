<?php
namespace GDO\ImportGWF3\Method;

use GDO\ImportGWF3\MethodImport;
use GDO\Core\Logger;
use GDO\Forum\GDO_ForumBoard;
use GDO\Forum\GDO_ForumThread;
use GDO\Forum\GDO_ForumPost;

final class ImportForum extends MethodImport
{
	public function run()
	{
		Logger::logCron("Importing Forum");
		$this->importBoards();
		$this->importThreads();
		$this->importPosts();
	}
	
	##############
	### Boards ###
	##############
	private function importBoards()
	{
		$query = "SELECT * FROM {$this->prefix}forumboard";
		$result = $this->gwfdb()->queryRead($query);
		$this->gdodb();
		$data = [];
		while ($row = mysqli_fetch_assoc($result))
		{
			if ($dat = $this->boardRow($row))
			{
				$data[] = $dat;
			}
		}
		$boards = GDO_ForumBoard::table();
		$this->gdodb()->disableForeignKeyCheck();
		$boards->truncate();
		$this->gdodb()->enableForeignKeyCheck();
		
		$boards->bulkInsert($boards->gdoColumns(), $data);
		
		$boards->rebuildFullTree();
		
		$count = count($data);
		Logger::logCron("Imported $count forum boards.");
	}
	
	private function boardRow(array $row)
	{
		return array(
			$row['board_bid'],
			$row['board_title'],
			$row['board_descr'],
			$this->gidornull($row['board_gid']),
			$this->gwfdatenow(),
			$this->systemID(),
			($row['board_options'] & 0x01) ? '1' : '0',
			($row['board_options'] & 0x04) ? '1' : '0',
			'0',
			'0',
			$this->idornull($row['board_pid']),
			'0',
			'0',
			'0',
		);
	}
	
	###############
	### Threads ###
	###############
	private function importThreads()
	{
		$query = "SELECT * FROM {$this->prefix}forumthread";
		$result = $this->gwfdb()->queryRead($query);
		$this->gdodb();
		$data = [];
		while ($row = mysqli_fetch_assoc($result))
		{
			if ($dat = $this->threadRow($row))
			{
				$data[] = $dat;
			}
		}
		$threads = GDO_ForumThread::table();
		$this->gdodb()->disableForeignKeyCheck();
		$threads->truncate();
		$this->gdodb()->enableForeignKeyCheck();
		
		$threads->bulkInsert($threads->gdoColumns(), $data);
		
		$count = count($data);
		Logger::logCron("Imported $count forum threads.");
	}
	
	private function threadRow(array $row)
	{
		if ($row['thread_firstdate'])
		{
			return array(
				$row['thread_tid'],
				$row['thread_bid'],
				$row['thread_title'],
				'0',
				$row['thread_viewcount'],
				($row['thread_options'] & 0x02) ? '1' : '0',
				$this->gwfdate($row['thread_firstdate']),
				$this->uidorguest($row['thread_uid']),
			);
		}
	}
	
	#############
	### Posts ###
	#############
	private function importPosts()
	{
		$query = "SELECT * FROM {$this->prefix}forumpost";
		$result = $this->gwfdb()->queryRead($query);
		$this->gdodb();
		$data = [];
		while ($row = mysqli_fetch_assoc($result))
		{
			if ($dat = $this->postRow($row))
			{
				$data[] = $dat;
			}
		}
		$posts = GDO_ForumPost::table();
		$this->gdodb()->disableForeignKeyCheck();
		$posts->truncate();
		$this->gdodb()->enableForeignKeyCheck();
		
		$posts->bulkInsert($posts->gdoColumns(), $data);
		
		$count = count($data);
		Logger::logCron("Imported $count forum threads.");
		
	}
	
	
}