# 一个老项目无法启动，npm版本问题
一个vue-cli3制作的项目，本地无法启动
## 找不到打包脚本
`package.json`中build指令为空，很奇怪。

正常的vue-cli生成的项目，package.json中的build会使用`vue-cli-service`来打包，而且会有`vue-cli`的依赖。
但这个明明是vue-cli3生成的项目中，却没有`vue-cli-service`，甚至没有`webpack`依赖。

__怎么打包？__


这个项目git中是提交了打包后的dist文件，所以生产环境部署是没问题的，但是本地新开发代码却无法打包，因为是老项目，猜测是交接的时候，本地没有提交部分文件（也很奇怪，难道本地有单独的package.json文件？why？）

## 不管了，先跑起来再说
因为这个项目是多 package项目，用lerna来管理，一个项目是node，一个项目是websrc。上面说的问题就是websrc下的vue项目。
猜测是lerna有单独配置，尝试通过lerna打包websrc，但是发现lerna只是引用了websrc下的package.json，而package.json并的build为空，所以还是不行，不是lerna的问题。

进入websrc目录，手动安装vue-cli-service,并尝试打包，各种版本问题和报错。
（省略一万字）
（因为本地是node16，vue3，vue-client3,而项目是vue-cli3制作的vue2的项目，中间各种版本报错）。

最终，本地用nvm重新安装node，切换当前node为16

安装打包依赖（因为不熟悉vue和打包，都是通过报错一个个添加的依赖）
```
 "devDependencies": {
    "@vue/cli-service": "^4.5.15",
    "@vue/compiler-sfc": "^3.2.21",
    "vue-template-compiler": "^2.6.14",
    "webpack": "^4.46.0",
    "less": "^4.1.2",
    "less-loader": "^7.3.0",
    "sass-loader": "^10.0.5",
    "node-sass": "5.0",
    "cache-loader": "^4.1.0",
```

配置`vue.config.js`
(vue默认entry是main.js，但项目里是index.js，得手动指定)

```
module.exports = {
    "outputDir": "public",
    "indexPath": "html/index.html",
    pages: {
      index: {
        // entry for the page
        entry: 'src/index.js',
      },
    }
  }
```

此时再运行`./node_modules/@vue/vue-cli-service/bin/cli.js build`，成功了！
但此时依然不知道老代码里为啥没有打包相关的依赖，奇怪！

## 生产部署
尝试部署到生产，失败
```
npm WARN read-shrinkwrap This version of npm is compatible with lockfileVersion@1, but package-lock.json was generated for lockfileVersion@2. I'll try to do my best with it!

...

npm ERR! code EUNSUPPORTEDPROTOCOL
npm ERR! Unsupported URL Type "npm:": npm:vue-loader@16.7.0
```

生产环境是
```
# node -v
v8.12.0
# npm -v
6.4.1
```

本地是node16，npm8。但是，从npm7开始lockfileVersion就是2了。
本地安装node8
```
➜  ~ node -v
v8.11.2
➜  ~ npm -v
5.6.0
```

warning消除，但是第二个报错还在，
```
npm ERR! code EUNSUPPORTEDPROTOCOL
npm ERR! Unsupported URL Type "npm:": npm:vue-loader@16.7.0
```
是因为"npm:xxx"这个语法，从npm6.9才支持，生产6.4不支持。

尝试本地降级npm到5.6
```
npm install -g  npm@5.6
```

打包，报错
```
npm ERR! code EINVALIDTAGNAME
npm ERR! Invalid tag name "vue-loader-v16@npm:vue-loader@^16.1.0": Tags may not have any characters that encodeURIComponent encodes.
```

看来是vue-cli3生成的项目，npm5.6还是支持不了
(也许调整相关依赖对小版本可以解决问题，但是不确定，太麻烦了）。决定还是升级下生产环境的npm


## 最终决定升级生产环境npm版本
```
node v8.17.0
npm 6.13.4
```

成功！

## 回想
现在回想为什么package.json中没有打包相关的依赖。估计当时作业遇到了和我类似的问题
- 本地尝试用vue-cli3开发（尝试新事物）
- 本地开发完成，没问题
- 生产部署，失败，遇到上面的各种问题
- 没办法，删除vue相关的依赖，只保留dist目录，部署成功！
- 本地开发的时候，因为node_modules都已经安装好了，所以不影响！！
但是对于接手的新人就很麻烦，也很困惑。

# 学习了什么呢
- vue-cli3
- lerna 多包管理工具
- npm不同版本的注意事项
	- 包版本允许前缀的用法("npm:xxx")，npm6.9才支持
	- lockfileVersion, npm7变为2
- 其他打包依赖
	- node-sass
	- sass-loader
	- less-loader
