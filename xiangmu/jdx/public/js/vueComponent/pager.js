
Vue.component('vue-pager', {
    template :
    '<div v-if="conf.totalPage>1" class="pagination pagination-centered">' +
    '<ul>' +
    '<li v-for="li in conf.pageRange" @click="pageClick(li.num)" class="{{li.className}}">' +
    '<a href="javascript:;">{{li.text}}</a>' +
    '</li>' +
    '</ul>' +
    '</div>',
    props:['conf'],
    watch : {
        'conf.totalPage' : function (){
            this.getPageRange()
        },
        'conf.currPage' : function (){
            this.getPageRange()
        },
        'conf.prevShow' : function (){
            this.getPageRange()
        },
        'conf.nextShow' : function (){
            this.getPageRange()
        }
    },
    methods : {
        getPageRange : function (){
            var start = 0
            var end = 0
            var showLen = this.conf.prevShow + this.conf.nextShow + 1
            var totalPage = Math.max(this.conf.totalPage, 1)
            var currPage = this.conf.currPage

            if (totalPage <= 1){
                start = end = 1
            }
            else if (totalPage <= showLen){
                start = 1
                end = totalPage
            }
            else {
                if (currPage <= this.conf.prevShow + 1){
                    start = 1
                    end = showLen
                }
                else if (currPage >= totalPage - this.conf.nextShow){
                    end = totalPage
                    start = totalPage - showLen + 1
                }
                else {
                    start = currPage - this.conf.prevShow
                    end = currPage + this.conf.nextShow
                }
            }

            this.conf.pageRange = []

            //上一页
            if (currPage != 1){
                this.conf.pageRange.push({num:currPage-1, text:'上一页'})
            }
            //第一页
            if (start >= 2){
                this.conf.pageRange.push({num:1, text:1})
            }
            //省略好
            if (start > 2){
                this.conf.pageRange.push({text:'..'})
            }
            //显示的页码列表
            for (var i=start; i<=end; i++){
                this.conf.pageRange.push({
                    num : i,
                    text : i,
                    className : (i==currPage) ? 'active' : ''
                })
            }
            //省略号
            if (end < totalPage-1){
                this.conf.pageRange.push({text:'..'})
            }
            //最后一页
            if (end <= totalPage-1){
                this.conf.pageRange.push({num:totalPage, text:totalPage})
            }
            //下一页
            if (currPage != totalPage){
                this.conf.pageRange.push({num:currPage+1, text:'下一页'})
            }
        },
        pageClick : function (i){
            if (!i){
                return false
            }
            if (i == this.conf.currPage){
                return false
            }

            this.conf.currPage = i
            this.getPageRange()
        }
    },
    compiled : function (){
        this.getPageRange()
    }
})