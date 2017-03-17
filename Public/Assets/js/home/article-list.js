/**
 * Created by Lin07ux on 2017/1/12.
 */

Vue.http.options.emulateJSON = true;
new Vue({
    el: '#lists',
    data: {
        articles: {
            lists: articles.lists || [],
            total: articles.total || 0,
            page: articles.page || 0
        },
        loading: false,
        timer: 0
    },
    created: function () {
        if (this.articles.lists.length < this.articles.total) {
            document.addEventListener('scroll', this.scrollFn);
        }
    },
    methods: {
        // 滚动事件的回调
        scrollFn: function () {
            if (this.loading) return false;

            // 如果已经设置了定时器则清除该定时任务
            if (this.timer) clearTimeout(this.timer);

            // 每隔100ms才能进行一次位置判断,减少卡顿的可能性
            var self = this;
            this.timer = setTimeout(function () {
                self.timer = 0;

                // 检查是否已经滚动到了底部,是的话就加载更多
                var body = document.body;
                var last = body.scrollHeight - body.offsetHeight - body.scrollTop;
                last < 100 && self.loadMore();
            }, 100);
        },

        // 加载更多的文章
        loadMore: function () {
            // 加载完成之后就不需要
            if (this.articles.lists.length >= this.articles.total) {
                document.removeEventListener('scroll', this.scrollFn);
                return false;
            }

            var articles = this.articles;
            var self = this;

            this.loading = true;
            this.$http.get('', { params: { page: this.articles.page + 1 } })
                .then(function (res) {
                    if (res.data.code) return false;

                    var data = res.data.data;
                    articles.total = data.total;
                    articles.page = data.page;

                    var len = data.lists.length;
                    for (var i = 0; i < len; i++) {
                        articles.lists.push(data.lists[i]);
                    }
                })
                .catch(function(){})
                .then(function(){ self.loading = false; });
        }
    }
});