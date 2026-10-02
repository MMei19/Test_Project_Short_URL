<?php
final class Click_Events {
 public $click_id=null;public $link_id='';public $clicked_at=null;private $pdo;
 public function __construct(PDO $pdo){$this->pdo=$pdo;}
 public function create():void{$driver=$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);$numberSql=$driver==='mysql'?'CAST(SUBSTRING(click_id,2) AS UNSIGNED)':'CAST(SUBSTR(click_id,2) AS INTEGER)';$next=(int)$this->pdo->query("SELECT COALESCE(MAX($numberSql),0)+1 FROM click_events")->fetchColumn();$this->click_id='C'.str_pad((string)$next,5,'0',STR_PAD_LEFT);$s=$this->pdo->prepare('INSERT INTO click_events(click_id,link_id,clicked_at) VALUES(?,?,?)');$s->execute([$this->click_id,$this->link_id,gmdate('Y-m-d H:i:s')]);}
 public function read():PDOStatement{return $this->pdo->query('SELECT click_id,link_id,clicked_at FROM click_events ORDER BY clicked_at DESC');}
 public function readById(string $id):PDOStatement{$s=$this->pdo->prepare('SELECT click_id,link_id,clicked_at FROM click_events WHERE click_id=?');$s->execute([$id]);return $s;}
 public function readByLinkId(string $id):PDOStatement{$s=$this->pdo->prepare('SELECT click_id,link_id,clicked_at FROM click_events WHERE link_id=? ORDER BY clicked_at DESC');$s->execute([$id]);return $s;}
 public function update():void{$s=$this->pdo->prepare('UPDATE click_events SET link_id=?,clicked_at=? WHERE click_id=?');$s->execute([$this->link_id,$this->clicked_at,$this->click_id]);}
 public function deleteByLinkId(string $id):void{$s=$this->pdo->prepare('DELETE FROM click_events WHERE link_id=?');$s->execute([$id]);}
}
