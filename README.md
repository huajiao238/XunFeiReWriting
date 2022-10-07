# XunFeiReWriting
讯飞文本改写PHP类库，支持链式调用

### 调用示例

```
XunFeiReWriting::getInstance()->setAppKey("你的app key")->setAppSecret("你的App secret")->setAppId("你的app ID")->setContent("要改写的文本内容")->get();
```
当然， 你也可以在类中将appKey、appSecret、appID等固定字段写死，然后调用：
```
XunFeiReWriting::getInstance()->setContent("要改写的内容")->get();
```
即可。

返回值为数组，示例如下：
```
[
	"code"  =>  200,     //201表示出了故障
	"message" => "改写完成后的文本内容"    //当code为201时， 表示为错误信息
]
```
