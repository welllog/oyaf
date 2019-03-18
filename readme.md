# 基于yaf搭建补充的项目快速开发框架

### 环境要求
1. php >= 7.1
2. yaf框架扩展
3. php.ini配置``yaf.use_namespace=1``

### nginx配置参考
```
server {
  listen ****;
  server_name  domain.com;
  root   document_root/public;
  index  index.php index.html index.htm;

  if (!-e $request_filename) {
    rewrite ^/(.*)  /index.php/$1 last;
  }

  location ~ \.php {
          try_files  $uri =404;
          fastcgi_split_path_info  ^(.+\.php)(/.+)$;
          fastcgi_pass   127.0.0.1:9000;
          fastcgi_index  index.php;
          fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
          fastcgi_param  SCRIPT_NAME  $fastcgi_script_name;
          include        fastcgi_params;
  }
}
```


###### 成功提示
> composer install安装，访问http://yourhost/index/,出现Hellow Word!, 表示运行成功,否则请查看php错误日志;

#### 配置
>* conf目录下
>* 重要配置：
>> 1. application.ini中配置loglevel日志级别
>> 2. application.ini中配置cachedriver缓存驱动，仅实现了file,redis驱动
>> 3. app.php中定义常量RUN_MODE运行环境
>> 4. dbconf.php中数据库连接配置

#### tips
* 缓存实现了psr-16标准，需要替换或增加缓存驱动，修改application/library/Ocache/Cache.php即可
* 日志实现了psr-3标准，目前实现了file,elasticsearch写入日志，如需扩展仅需实现application/library/Olog/Output接口,并在
application/Bootstrap.php中注入
* 验证器采用了vlucas/valitron，查看相关文档地址:https://github.com/vlucas/valitron

#### 查询构造器(详细用法查看代码)
>* DB::table('user')->insert(['name' => 'jack', 'age' => 24]);
>* DB::table('user')->insert([['name' => 'linda', 'age' => 21], ['name' => 'bob', 'age' => 24]]);
>* DB::table('user')->where('id', '=', 1)->update(['name' => 'remi']);
>* whereNull('username')
>* whereNotNull('username')
>* whereIn('id', [1, 2, 3])
>* whereNotIn('id', [1, 2, 3])
>* whereBetween('id', [1, 9])
>* whereNotBetween('id', [1, 9])
>* whereColumn('id', '>', 'parent_id')
>* where('username', '=', 'job')->where('age', '>', 23)
>* where([['username', '=', 'job'], ['age', '>', 23]])
>* whereRaw('`id`>? and `status`=?', [10, 1])

#### controller
获取请求参数
>* $this->getPost()
>* $this->getPostForm()
>* $this->getQuery()
>* $this->getParams()

验证参数
> $this->makeValidator($params)->rule(...)->validate()

返回json响应(注意必须加上return)
> return $this->ajaxReturn()

#### model
* $userModel->all()
* $userModel->find($userid);
* $userModel->where(...)
* $userModel->useBuild()->where(...)->first()

model属性设置
* protected $table = 'user';
* protected $primaryKey = 'id';
 