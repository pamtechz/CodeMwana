<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
if (!Auth::check()) json_response(['ok'=>false,'message'=>'Your session ended. Sign in and try again.'],401);
if (!is_post()) json_response(['ok'=>false,'message'=>'Method not allowed.'],405);
verify_csrf();
$data=json_decode(file_get_contents('php://input')?:'{}',true);
if(!is_array($data))json_response(['ok'=>false,'message'=>'The project request is not valid.'],422);
$title=trim((string)($data['title']??''));$code=(string)($data['code']??'');
if(mb_strlen($title)<2||mb_strlen($title)>120)json_response(['ok'=>false,'message'=>'Use a project title between 2 and 120 characters.'],422);
if(trim($code)==='')json_response(['ok'=>false,'message'=>'Write some MwanaCode before saving the project.'],422);
if(mb_strlen($code)>30000)json_response(['ok'=>false,'message'=>'The project is larger than the 30,000-character limit.'],422);
try{$id=Learning::saveProject((int)current_user()['id'],['id'=>(int)($data['id']??0),'title'=>$title,'code'=>$code]);json_response(['ok'=>true,'id'=>$id,'message'=>'Project saved.']);}catch(Throwable $e){json_response(['ok'=>false,'message'=>config('app.debug')?$e->getMessage():'The project could not be saved.'],500);}
