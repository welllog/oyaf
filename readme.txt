可以按照以下步骤来部署和运行程序:
1.请确保机器已经安装了Yaf框架, 并且已经加载入PHP;
2.把oyaf目录Copy到Webserver的DocumentRoot目录下;
3.需要在php.ini里面启用如下配置:yaf.use_namespace=1
4.重启Webserver;
5.访问http://yourhost/oyaf/,出现Hellow Word!, 表示运行成功,否则请查看php错误日志;

nginx配置参考
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
