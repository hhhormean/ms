/////////////////////////////////////////////////////////////////////////////////////////////////
//Gandalf : software define storage upload sdk
/////////////////////////////////////////////////////////////////////////////////////////////////

var SliceState = {"failed" : -1, "ready" : 0, "waiting" : 1, "uploading" : 2, "success" : 3};
var CallbackState = {"failed" : 800, "lost" : 801, "giveup" : 802, "success" : 0};

//start upload
(function () {

    //callback array
    window.callbacks = [];
    //file upload array
    window.producers = [];
    //upload file fucntion
    window.LCUploader = function (obj) {
        var cb = new Object();
        cb.uploadUrl = obj.uploadUrl;
        cb.file = obj.file;
        cb.uc1 = obj.uc1;
        cb.uc2 = obj.uc2;
        cb.fileKey = obj.fileKey;
        cb.uploadType = 1;
        cb.load = obj.load;
        cb.progress = obj.progress;
        cb.error = obj.error;
        cb.abort = obj.abort;
        window.callbacks.push(cb);

        var pr = new window.Producer();
        window.producers.push(pr);
        var simpleAjax = new window.simpleA();

        pr.tryProduce(cb.uploadUrl, cb.file, cb, simpleAjax);
    }

    window.SDSAbort = function (fileKey) {
        for (var i = producers.length - 1; i >= 0; i--) {
            if (producers[i].uploadOption.fileKey == fileKey) {
                producers[i].xhrAbort();
            }
        };
    }

})();

//slice upload
(function () {
    window.Consumer = function () {

        this.xhr = null;
        this.sdsParent = null;

        //SliceInfo: uploadUrl, filename, totalSize, sliceIndex, sliceSize, sliceData
        this.sliceUpload = function (sliceInfo, _parent) {

            if (!_parent)
                return;

            this.sdsParent = _parent;
            this.sdsParent.sliceState[sliceInfo.sliceIndex] = SliceState.uploading;

            this.xhr = new XMLHttpRequest();
            this.xhr.upload.addEventListener("progress", function (e) { _parent.progress(e, sliceInfo.sliceIndex, this.sdsParent) }, false);
            this.xhr.addEventListener("loadstart", function (e) { _parent.loadstart(e, sliceInfo.sliceIndex, this.sdsParent) }, false);
            this.xhr.addEventListener("error", function (e) { _parent.error(e, sliceInfo.sliceIndex, this.sdsParent) }, false);
            this.xhr.addEventListener("abort", function (e) { _parent.abort(e, sliceInfo.sliceInfo.sliceIndex, this.sdsParent) }, false);
            this.xhr.addEventListener("load", function (e) { _parent.load(e, sliceInfo.sliceIndex, this.sdsParent) }, false);

            var start = sliceInfo.sliceIndex * sliceInfo.sliceSize;
            var content = start == -1 ? "bytes *" : "bytes " + (start + 1) + "-" + (start + sliceInfo.sliceData.size) + "/" + sliceInfo.totalSize;
            
            this.xhr.open("POST", sliceInfo.uploadUrl, true)
            this.xhr.setRequestHeader("Pragma", "letv_LCUploader_1.0");
            this.xhr.setRequestHeader("X_FILENAME", encodeURI(sliceInfo.filename));
            this.xhr.setRequestHeader("Content-Range", content);

            this.xhr.send(sliceInfo.sliceData);
        };

        this.xhrAbort = function () {
            this.xhr && this.xhr.abort();
        }
    };
})();


//producer upload
(function () {
    window.Producer = function () {
        
        this.currentFile = {};
        this.sliceCount = 0;  //slice count of file
        this.sliceState = []; //slice status : -n -> failed count, 0 -> waiting upload, 1 -> upload success
        this.slicePost = []; //slice uploaded size already
        this.sliceSize = 10485760;  //10M
        this.sliceStack = {};

        this.xhr = null;
        this.uploadOption = {};

        this.uploadUrl = "";

        this.concurrentCount = 1;
        this.failedCount = 0;
        this.maxFailedCount = 20;
        this.Consumers = [];

        this.format = function () {
            var b, a = arguments,
                d = a[0] || {},
                f = 1,
                e = a.length;
            if (!e) return null;
            for (e === f && (d = this, --f) ; f < e; f++)
                if (null != (b = a[f]))
                    for (var c in b) d[c] = b[c];
            return d
        };

        this.evalTo = function (string) {
            try {
                return (new Function("return " + string))();
            } catch (e) {
                console && console.log("Error: " + string);
            }
        };

        this.tryProduce = function(initUrl, file, option, simpleAjax) {

            var data = { videoname: encodeURIComponent(file.name) }
            var _simpleAjax = simpleAjax;
            var argument = this.format(data, {
                client: "html5",
                uploadtype: option.uploadType,
                file_size: file.size,
                t: (new Date).getTime(),
                uc1: option.uc1,
                uc2: option.uc2
            })

            var _self = this;

            if (window.html5UploadCookie.getItem(option.fileKey)) {
                argument = { token: window.html5UploadCookie.getItem(option.fileKey), uploadtype: option.uploadType };
            }

            simpleAjax.get(initUrl, argument, function (reqData) {
                try {console.log(reqData);
                    var data = eval("(" + reqData + ")");
                } catch (e) {
                    var data = { code: 999 };
                }

                if (data.code == 0) {
                    var uploadPos = data.data.upload_size || 0;
                    var url = data.data.upload_url.substr(0, data.data.upload_url.length - 10);
                    _self.tryUpload(url, file, uploadPos, option);

                } else {
                    if (this.option.init) {
                        option.init(data);
                    }
                }
            });
        };

        this.tryUpload = function (url, file, transferedsize, option) {

            this.uploadUrl = url;
            this.currentFile = file;
            this.uploadOption = option;
            this.sliceStack.transferedSize = transferedsize < 0 ? 0 : transferedsize;
            this.sliceStack.startPosition = this.sliceStack.transferedSize;
            this.sliceStack.initTime = 0;

            this.sliceCount = Math.ceil(file.size / this.sliceSize);  //slice count
            var slicePos = Math.floor(this.sliceStack.startPosition / this.sliceSize); //start upload position

            //resume upload
            for (var index = 0; index < slicePos; index++) {
                this.sliceState[index] = SliceState.success;
                this.slicePost[index] = this.sliceSize;
            }

            for (var index = slicePos; index < this.sliceCount; index++) {
                this.sliceState[index] = SliceState.ready;
                this.slicePost[index] = 0;
            }
          
            var runCount = Math.min(this.concurrentCount, this.sliceCount - 1);
            runCount = Math.max(runCount, 1);
            for (var index = 0; index < runCount; index++) {

              this.consumerUpload(slicePos + index);
            }

        };

        this.consumerUpload = function(sliceIndex) {
            //SliceInfo: uploadUrl, filename, totalSize, sliceIndex, sliceSize, sliceData

            var consumer = new window.Consumer();
            this.Consumers.push(consumer);

            sliceInfo = {};
            sliceInfo.uploadUrl = this.uploadUrl;
            sliceInfo.filename = this.currentFile.name;
            sliceInfo.totalSize = this.currentFile.size;
            sliceInfo.sliceIndex = sliceIndex;
            sliceInfo.sliceSize = this.sliceSize;
            start = sliceInfo.sliceIndex * sliceInfo.sliceSize;
            sliceInfo.sliceData = this.fileSlice(this.currentFile, start);

            consumer.sliceUpload(sliceInfo, this);
        };

        this.fileSlice = function (file, start) {

            var blob;
            start = start || 0;
            var range = Math.min(file.size, start + this.sliceSize);
            if (start != -1) {
                if (file.slice) {
                    blob = file.slice(start, range);
                } else if (file.webkitSlice) {
                    blob = file.webkitSlice(start, range);
                } else if (file.mozSlice) {
                    blob = file.mozSlice(start, range);
                } else {
                    blob = file;
                }
            } else {
                return null;
            }
            return blob;
        };

        this.load = function (e, sliceIndex, _self) {

          //upload failed , server return stauts != 200
          var res = this.evalTo(e.target.responseText);
          if (!res.totalsize && res.status != '200') {
            if (this.uploadOption.error) {
                this.uploadOption.error(CallbackState.failed, e);
            }
            window.html5UploadCookie.removeItem(this.uploadOption.fileKey);
            this.xhrAbort();
            return;
          }

          var tokenUrl = this.uploadUrl.split('token').pop();
          var videoToken = tokenUrl.substr(1, tokenUrl.length - 1).split('&')[0];
          window.html5UploadCookie.setItem(this.uploadOption.fileKey, videoToken, 30);

          this.sliceStack.starttime = (new Date()).getTime();
          if (res.transferedsize && res.transferedsize == res.totalsize) {
            if (this.uploadOption.load) {
                window.html5UploadCookie.removeItem(this.uploadOption.fileKey);
                this.uploadOption.load(CallbackState.success, e);
                return;
            }
          }

          this.sliceState[sliceIndex] = SliceState.success;

          //file send complete
          //if (sliceIndex == this.sliceCount - 1) {
            //this.uploadOption.load(0, e);
            //return;
          //}

          //check ready to upload slice
          for (var index = sliceIndex + 1; index < this.sliceCount - 1; index++) {

            if (this.sliceState[index] == SliceState.ready) {
                this.consumerUpload(index);                
              return;
            }
          }

          var flag = true;
          for (var index = 0; index < this.sliceCount - 1; index++) {
            if (flag && this.sliceState[index] != SliceState.success) {
              flag = false;
              break;
            }
          }

          //send last slice
          if (flag) {
                this.consumerUpload(this.sliceCount - 1);
            }
        };

        this.loadstart = function (e, sliceIndex, _self) {
          this.sliceStack.initTime = (new Date()).getTime(); 
        };

        this.progress = function (e, sliceIndex, _self) {
          //
          this.slicePost[sliceIndex] = e.loaded;

          var transferedSize = 0;
          for (var index = 0; index < this.slicePost.length; index++) {
            transferedSize += this.slicePost[index];
          }

          this.sliceStack.transferedSize = transferedSize;

          var pc = parseInt(this.sliceStack.transferedSize / this.currentFile.size * 100);
          var deltaTime = ((new Date()).getTime() - (this.sliceStack.startTime || this.sliceStack.initTime)) / 1000;
          var rate = e.loaded / deltaTime;
          rate = rate / 1024;
          rate = rate > 1024 ? (((rate / 1024 * 10) >> 0) / 10).toFixed(1) + "M/s" : (((rate * 10) >> 0) / 10).toFixed(1) + "K/s";

          //delete file while upload file
          if (isNaN(pc)) {
            this.uploadOption.progress(CallbackState.lost, e);
            this.xhrAbort();
          } else if (this.uploadOption.progress) {
            this.uploadOption.progress(CallbackState.success, { progress: pc + "%", speed: rate, file: this.currentFile });
          }
        };

        this.error = function (e, sliceIndex, _self) {
          if (this.failedCount > this.maxFailedCount) {
            if (this.uploadOption.error) {
                this.uploadOption.error(CallbackState.failed, e);
            }
            this.xhrAbort();
          } else {
            this.failedCount += 1;
            this.consumerUpload(sliceIndex);
          }
        };

        this.abort = function (e, sliceIndex, _self) {
          if (this.uploadOption.abort) {
                this.uploadOption.abort(e);
            }
        };

        this.xhrAbort = function () {
            for (var index = 0; index < this.Consumers.length; index++) {
                if (this.Consumers[index]) {
                    this.Consumers[index].xhrAbort();
                }
            }
        };
    };
})();


//encapsulate ajax
(function () {
    function Glib() {
        this.JsonToParam = function (json) {
            var s = { a: 1, b: 2 }
            var arr = [];
            for (var p in json) {
                arr.push(p + "=" + json[p]);
            }
            return arr.join('&');
        }
    };

    function Ajax() {
        this.GXHR = null;
        this.CALLBACK = function () { };
        Glib.apply(this)
    };

    Ajax.prototype = {
        reviveXHR: function () {
            if (this.GXHR) { return }
            var _XHR,
            _msieXHR = [
            'Msxml2.XMLHTTP.5.0',
            'Msxml2.XMLHTTP.4.0',
            'Msxml2.XMLHTTP.3.0',
            'Msxml2.XMLHTTP',
            'Microsoft.XMLHTTP'
            ];
            for (var i = 0, l = _msieXHR.length; i < l; i++) {
                try {
                    if (_XHR = new ActiveXObject(_msieXHR[i])) break;
                } catch (e) {
                    _XHR = null;
                }
            }

            if (_XHR || (_XHR = new XMLHttpRequest, typeof XMLHttpRequest != 'undefined')) {
                return _XHR;
            }
            if (!_XHR) throw new Error("connection object not define.");
        },
        scope: function (fn, scope) {
            return function () { return fn.apply(scope) }
        },
        fmtFn: function (fn) {
            if (typeof fn == 'function') {
                this.CALLBACK = fn;
            }
            var _callback = this.CALLBACK || new Function;
            return _callback;
        },

        stateHandle: function () {
            var _self = this.GXHR;
            if (_self.readyState == 2) {
            } else if (_self.readyState == 4) {
                if (_self.status == 200) {
                    this.CALLBACK(_self.responseText);
                }
            }

        },
        fmtParam: function (data) {
            var urlParam = null;
            if (typeof data != 'string' && typeof data == 'object') {
                urlParam = this.JsonToParam(data);
            }
            return urlParam || data;
        },
        request: function (url, data, callback, method) {
            method = method || "GET";
            if (method == 'GET') {
                if (url.indexOf('?') != -1)
                    url += "&";
                else
                    url += "?";
                url += this.fmtParam(data);
            }
            var _self = this;
            _self.CALLBACK = this.fmtFn(callback);

            _self.GXHR = _self.GXHR || _self.reviveXHR();
            _self.GXHR.onreadystatechange = _self.scope(_self.stateHandle, _self);
            _self.GXHR.open(method, url, true);
            _self.GXHR.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            _self.GXHR.send(_self.fmtParam(data) || null);
        },

        get: function (url, data, callback) {
            return this.request(url, data, callback, "GET");
        },
        post: function (url, data, callback) {
            var url = url + '?uc1=5&uc2=8';
            return this.request(url, data, callback, 'POST');

        },
        getJSON: function (url, data, fn) {
            var oldScript = document.getElementById(url);
            if (oldScript) {
                oldScript.setAttribute("src", url);
                return;
            }
            var head = document.getElementsByTagName('head')[0];
            var script = document.createElement("script");
            script.setAttribute('type', 'text/javascript');
            script.setAttribute('src', url);
            script.setAttribute('id', url);
            head.appendChild(script);
            window['callback'] = function (data) {
                try {
                    fn && fn(data);
                } catch (e) { }
            };
            script.onload = script.onreadystatechange = function () {
                if ((!this.readyState || this.readyState === "complete" || this.readyState === "loaded")) {

                    script.onload = script.onreadystatechange = null; //IE内存溢出
                    if (head && script.parentNode) {
                        head.removeChild(script);
                    }
                }
            };
        }
    }
    window.simpleA = Ajax;
    window.Ajax = new Ajax;
})();

//cookie save token resume
(function () {
    window.html5UploadCookie = {
        setItem: function (key, value, expiresDays) {
            var date = new Date();
            date.setTime(date.getTime() + expiresDays * 24 * 3600 * 1000);
            document.cookie = key + "=" + value + "; expires=" + date.toGMTString();
        },
        getItem: function (key) {
            var strCookie = document.cookie;
            var arrCookie = strCookie.split("; ");
            for (var i = 0; i < arrCookie.length; i++) {

                var arr = arrCookie[i].split("=");
                if (arr[0] == key) {
                    return arr[1];
                }
                if (i == arrCookie.length - 1) {
                    return false;
                }
            }
        },
        removeItem: function (key) {
            window.html5UploadCookie.setItem(key, "", -1);
        }
    };
})();


