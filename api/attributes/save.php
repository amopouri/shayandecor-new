<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/lib/bootstrap.php';
require_method('POST'); require_admin(true);
$in=input(); $id=n($in['id']??0); $type=s($in['type']??'size',20); $name=s($in['name']??'',120); $value=s($in['value']??'',60); $sort=n($in['sort_order']??0,0,100000);
if(!in_array($type,['size','color','shaneh'],true)) fail('نوع ویژگی نامعتبر است.',422);
if($name==='') fail('عنوان ویژگی الزامی است.',422);
if($id){
  if(!q('SELECT id FROM attributes WHERE id=? AND type=?',[$id,$type])->fetch()) fail('ویژگی پیدا نشد.',404);
  q('UPDATE attributes SET name=?,value=?,sort_order=? WHERE id=?',[$name,$value?:null,$sort,$id]);
}else{
  if(q('SELECT id FROM attributes WHERE type=? AND name=? LIMIT 1',[$type,$name])->fetch()) fail('این مقدار قبلاً ثبت شده است.',409);
  q('INSERT INTO attributes(type,name,value,sort_order) VALUES(?,?,?,?)',[$type,$name,$value?:null,$sort]); $id=(int)db()->lastInsertId();
}
$item=q('SELECT id,type,name,value,sort_order FROM attributes WHERE id=?',[$id])->fetch();
ok('ویژگی ذخیره شد.',['item'=>$item]);
