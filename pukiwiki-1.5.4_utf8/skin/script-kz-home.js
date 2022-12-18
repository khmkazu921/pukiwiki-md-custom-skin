// Menu Toggle /////////////////////////////////////////////////////////////

const breakPoint = 1024;

function ShowAndHide(win){
    if( win > breakPoint ){
        $('#menuButton').hide();
        $('#menuList').show();
        $('#menuList').css({'flex-direcrion':'row'});
    } else {
        $('#menuButton').show();
        $('#menuButton').removeClass('open');
    }
}

$(function (){
    ShowAndHide( $(window).width() );
    $('#menuButton a').on('click', function () {
	$('#menuList').slideToggle(500);
	console.log('ok');
    });
    var currentWidth = $(window).width();
    $(window).resize(function(){
	if (currentWidth != $(window).width()) {
	    ShowAndHide( $(window).width() );
	}
    });
});

function openNav() {
    $("#menuList").css({'min-width': '200px',
                       'width': '200px',
                       'padding-left': '2rem',
                       'padding-right': '2rem'});
    $("#article").css({'width': 'calc(100% - 200px - 8rem)'});
}

function closeNav() {
    $("#menuList").css({'min-width': '0',
                       'width': '0',
                       'padding-left': '0rem',
                       'padding-right': '0rem'});
    $("#article").css({'width': 'calc(100% - 4rem)'});
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
            $('#pre-'+_selectIndex+' .switch').css({'display':'initial'});
        } else {
            // your element doesn't have overflow
            $('#pre-'+_selectIndex+' .switch').css({'display':'none'});
        }
        _selectIndex++;
    });
}

$(function (){
    hljs.initHighlightingOnLoad();

    var blocks = document.querySelectorAll('pre code.hljs');
    var _selectIndex = 0;
    Array.prototype.forEach.call(blocks, function(block) {
        block.parentElement.setAttribute('id','pre-'+_selectIndex);
        block.setAttribute('id','code-'+_selectIndex);
        var language = block.result.language;
        var html_switch = '<div class="code-header"><label class="hljs-label">'+language+'</label><label class="switch"><input type="checkbox" onclick="toggleWrapText(this)" value='+_selectIndex+' wrap=false><span class="slider round"></span></label></div>';
        block.insertAdjacentHTML("beforebegin",`${html_switch}`)
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

