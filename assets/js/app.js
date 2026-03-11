(function(){
  function debounce(fn, wait){
    var t;
    return function(){
      var ctx=this, args=arguments;
      clearTimeout(t);
      t=setTimeout(function(){fn.apply(ctx,args);}, wait);
    };
  }

  var search = document.querySelector('.app-search');
  if(!search) return;

  var onInput = debounce(function(){
    var q = search.value.trim();
    if(q.length === 0) return;
  }, 300);

  search.addEventListener('input', onInput);
})();
