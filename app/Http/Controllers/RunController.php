<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\RRuns;
use App\Hitokoto;

class RunController extends Controller
{
    //获取随机一言
    public function getHitokoto(Request $request){
        if($request->has('type')) $url = 'http://v1.alapi.cn/api/hitokoto?format=json&type='.$request->type;
        else $url = 'http://v1.alapi.cn/api/hitokoto?format=json';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $returnjson=curl_exec($curl);
        if($returnjson){
            //整理返回数据
            $json = json_decode($returnjson);
            if($json->code == 200){
                $hitokoto = new Hitokoto();
                $hitokoto['hitokoto'] = $json->data->hitokoto;
                $hitokoto['type'] = $json->data->type;
                $hitokoto['from'] = $json->data->from;
                $hitokoto['creator'] = $json->data->creator;
                $hitokoto->save();
                $json->data->id = $hitokoto->id;
                return returnData(true, "操作成功", $json->data);
            }else{
                return returnData(false, $json);
            }
        }else{
            return returnData(false, curl_error($curl));
        }
    }

    //跑步开始
    public function doStart(Request $request){
        if($request->has('rid')){
            $run = new RRuns();
            try {
                DB::beginTransaction();
                    // 跑步初始数据
                    $run->fillable(array_keys($request->all()));
                    $run->fill($request->all());
                    $run->save();
                DB::commit();
                // 处理返回数据
                $data = RRuns::where('ruid', $run->id)->first();
                return returnData(true, '操作成功', $data);
            } catch (\Throwable $th) {
                DB::rollBack();
                return returnData(false, $th);
            }
        }else{
            return returnData(false, '缺少rid');
        }
    }

    //跑步结束
    public function doEnd(Request $request){
        if($request->has('ruid')){
            $run = null;
            if($request->has('distance') && 
                $request->has('calorie') && 
                $request->has('speed_top') && 
                $request->has('speed_low') && 
                $request->has('speed') && 
                $request->has('time_end') && 
                $request->has('time_run')&& 
                $request->has('latitude_end')&& 
                $request->has('longitude_end')&& 
                $request->has('img'))
            {
                if(RRuns::where('ruid', $request->ruid)->update($request->all())){
                    return returnData(true, '操作成功', RRuns::where('ruid', $request->ruid)->first());
                }
                else return returnData(false, '保存失败', null);
            }else{
                return returnData(false, '缺少必须参数，已传参数见data', array_keys($request->all()));
            }
        }else if($request->has('rid')){
            $run = new RRuns();
            try {
                DB::beginTransaction();
                    // 跑步初始数据
                    $run->fill($request->all());
                    $run->save();
                DB::commit();
                // 处理返回数据
                $data = RRuns::where('ruid', $run->id)->first();
                return returnData(true, '操作成功', $data);
            } catch (\Throwable $th) {
                DB::rollBack();
                return returnData(false, $th);
            }
        }else{
            return returnData(false, '缺少ruid或者rid');
        }
    }
}