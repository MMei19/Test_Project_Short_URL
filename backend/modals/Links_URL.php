<?php
final class Links_URL {
 public $link_id=null; public $original_url=''; public $short_code=null; public $status='active'; public $expires_at=null; private $pdo; private $redirectUrl;
 public function __construct(PDO $pdo,string $redirectUrl){$this->pdo=$pdo;$this->redirectUrl=$redirectUrl;}
 private function rows(string $where=''):string { return "SELECT link_id,short_code,short_url,original_url,clicks,status,expires_at,created_at,last_clicked_at FROM links_URL $where"; }
 public function create():void { $url=filter_var(trim($this->original_url),FILTER_VALIDATE_URL); if(!$url||!in_array(strtolower(parse_url($url,PHP_URL_SCHEME)?:''),['http','https'],true))throw new InvalidArgumentException('กรุณากรอก URL ที่ขึ้นต้นด้วย http:// หรือ https://'); $code=trim((string)$this->short_code);if($code==='')$code=substr(bin2hex(random_bytes(5)),0,7);if(!preg_match('/^[A-Za-z0-9_-]{3,32}$/',$code))throw new InvalidArgumentException('นามแฝงใช้ได้เฉพาะ a-z, A-Z, 0-9, _ และ - จำนวน 3-32 ตัว');$driver=$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);$numberSql=$driver==='mysql'?'CAST(SUBSTRING(link_id,2) AS UNSIGNED)':'CAST(SUBSTR(link_id,2) AS INTEGER)';$next=(int)$this->pdo->query("SELECT COALESCE(MAX($numberSql),0)+1 FROM links_URL")->fetchColumn();$this->link_id='L'.str_pad((string)$next,4,'0',STR_PAD_LEFT);$this->short_code=$code;$shortUrl=$this->redirectUrl.$code;$createdAt=gmdate('Y-m-d H:i:s');$s=$this->pdo->prepare('INSERT INTO links_URL(link_id,short_code,short_url,original_url,clicks,status,expires_at,created_at) VALUES(?,?,?,?,0,?,?,?)');$s->execute([$this->link_id,$code,$shortUrl,$url,$this->status,$this->expires_at,$createdAt]); }
 public function read():PDOStatement{return $this->pdo->query($this->rows('ORDER BY created_at DESC'));}
 public function readById(string $id):PDOStatement{$s=$this->pdo->prepare($this->rows('WHERE link_id=?'));$s->execute([$id]);return $s;}
 public function update():void{$s=$this->pdo->prepare('UPDATE links_URL SET original_url=?,status=?,expires_at=? WHERE link_id=?');$s->execute([$this->original_url,$this->status,$this->expires_at,$this->link_id]);}
 public function deleteByLinkId(string $id):bool{$s=$this->pdo->prepare('DELETE FROM links_URL WHERE link_id=?');$s->execute([$id]);return $s->rowCount()>0;}
 public function withShortUrl(array $row):array{$row['short_url']=$this->redirectUrl.$row['short_code'];return $row;}
}
