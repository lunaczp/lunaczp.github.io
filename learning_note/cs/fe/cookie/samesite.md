# SameSite
## 遇到的问题
A(y.my.com)通过iframe内嵌B(x.my.com)，A、B各自维护自己的登录态。(A后端是Node，B后端是PHP）

B登录态失效，需要重新登录，走统一的sso跳转到飞书扫码登录，结果总是失败，无法登录成功。看Chrome报错。

```Indicate whether to send a cookie in a cross-site request by specifying its SameSite attribute
```
因为feishu.cn要设置cookie，但A页面是y.my.com，且飞书没设置SameSite，Chrome默认=Lax，禁止设置cookie


## 原理
[SameSite](https://web.dev/i18n/en/samesite-cookies-explained/)

## 类似问题
- [CSDN类似问题](https://blog.csdn.net/yhyc812/article/details/108623844)
