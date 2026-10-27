<?php
namespace flipbook;
if(!defined('IN_DISCUZ'))exit('Access Denied');
class table_flipbook_supplier extends table_flipbook_base {
 protected $orderfields=['supplierid','name','displayorder','available'];
 public function __construct(){$this->_table='flipbook_supplier';$this->_pk='supplierid';parent::__construct();}
}
