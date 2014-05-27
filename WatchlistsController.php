<?php
/**
 * Watchlists Controller
 *
 * PHP version 5.4
 *
 */
class WatchlistsController extends AppController {
	/**
	 * Watchlists name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'Watchlists';
	var	$uses	=	array('Watchlists');
	var $helpers = array('Mailto','Html','General');
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
	}
	
	public function my_watchlist_project()
	{
		$this->layout = 'lay_watchlist';
		$this->set('title_for_layout','My Watchlist Project');
	}
	
	
	public function my_watchlist_job()
	{
		$this->layout = 'lay_watchlist';
		$this->set('title_for_layout','My Watchlist Job');
	}
	
	public function my_watchlist_expert()
	{
		$this->layout = 'lay_watchlist';
		$this->set('title_for_layout','My Watchlist Expert');
	}
	
	
	public function my_watchlist_leader()
	{
		$this->layout = 'lay_watchlist';
		$this->set('title_for_layout','My Watchlist Leader');
	}
	
	
	
	
}