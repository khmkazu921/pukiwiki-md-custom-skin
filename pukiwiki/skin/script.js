// Menu Toggle /////////////////////////////////////////////////////////////

const breakPoint = 1024;

$(function (){
    if( $(window).width() < breakPoint ){
        $('#menuList').addClass('close');
    }
    $(window).resize(function(){
        if( $(window).width() < breakPoint ){
            $('#menuList').addClass('close');
        } else {
            $('#menuList').removeClass('close');
            $("#article").removeClass('close-menu');
        }
    });
});

function toggleMenuList() {
    $('#menuList').toggleClass('close');
    $("#article").toggleClass('close-menu');
}

// Smooth Scrool /////////////////////////////////////////////////////////////

function toggleWrapText(item) {
    var $table = $('#code-'+item.value+' table.hljs-ln');

    if(item.checked) {
        $('#code-'+item.value).css(
            {'word-break': 'break-all', 'white-space': 'pre-wrap'});
        if($table[0])
            $table.css({'width': '100%'});
    } else {
        $('#code-'+item.value).css(
            {'word-break': 'normal', 'white-space': 'pre'});
        if($table[0])
            $table.css({'width': 'max-content'});
    }
}


function toggleShowSwitch() {
    var blocks = document.querySelectorAll('pre code.hljs');
    var _selectIndex = 0;
    Array.prototype.forEach.call(blocks, function(block) {
        if (block.offsetWidth < block.scrollWidth) {
            // your element have overflow
            $('#pre-'+_selectIndex+' .wrap-switch').css({'display':'initial'});
        } else {
            // your element doesn't have overflow
            $('#pre-'+_selectIndex+' .wrap-switch').css({'display':'none'});
        }
        _selectIndex++;
    });
}

function toggleMoreCode(id) {
    $("#pre-"+id).toggleClass("open");
}

$(function (){
    hljs.highlightAll();

    var blocks = document.querySelectorAll('pre code.hljs');
    var _selectIndex = 0;
    Array.prototype.forEach.call(blocks, function(block) {
        block.parentElement.setAttribute('id','pre-'+_selectIndex);
        block.setAttribute('id','code-'+_selectIndex);
        var language = block.result.language;
        var header = `<div class="code-header"><label class="hljs-label">${language}</label><div class="switch-wrapper">`
        if( $("#pre-"+_selectIndex).height() >= 150 ) {
            header += `<label class="switch"><input type="checkbox" onclick="toggleMoreCode(${_selectIndex})" value=${_selectIndex} wrap=false><span class="slider round"></span></label>`;
        }
        header += `<label class="switch wrap-switch"><input type="checkbox" onclick="toggleWrapText(this)" value=${_selectIndex} wrap=false><span class="slider round"></span></label></div>`;
        if( $("#pre-"+_selectIndex).height() >= 150 ) {
            header += `<div class="code-fade"></div>`;
        }
        header += `</div>`;

        block.insertAdjacentHTML("beforebegin",`${header}`)
        _selectIndex++;        
    });
    hljs.initLineNumbersOnLoad();
    
    toggleShowSwitch();
    
    $(window).resize(function(){
        toggleShowSwitch();
    });

    // $('code.hljs:has(table)').delay(100).queue(function() {
    // $('pre:has(code)').addClass('hljs-ln-outer-code');
    
    $(function(){
        $('code.hljs:has(table)').addClass('hljs-ln-outer-code');
    });    
});
