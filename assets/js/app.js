(function(){
  function debounce(fn, wait){
    var t;
    return function(){
      var ctx=this, args=arguments;
      clearTimeout(t);
      t=setTimeout(function(){fn.apply(ctx,args);}, wait);
    };
  }

  function qs(sel, root){
    return (root || document).querySelector(sel);
  }

  function setCoverPreview(url){
    var img = qs('[data-cover-preview-img]');
    var empty = qs('[data-cover-preview-empty]');
    if(!img || !empty) return;

    if(url && typeof url === 'string'){
      // Normalize relative paths (e.g., uploads/covers/..)
      if(url.indexOf('http://') !== 0 && url.indexOf('https://') !== 0){
        if(url.charAt(0) !== '/'){
          url = '/' + url;
        }
        url = (window.APP_BASE_PATH || '') + url;
      }
    }

    if(url){
      img.src = url;
      img.style.display = '';
      empty.style.display = 'none';
    } else {
      img.removeAttribute('src');
      img.style.display = 'none';
      empty.style.display = '';
    }
  }

  function initAdminBookForm(){
    var bookInput = qs('[data-book-input]');
    var coverInput = qs('[data-cover-input]');
    var clearBook = qs('[data-clear-book]');
    var clearCover = qs('[data-clear-cover]');
    var titleInput = qs('input[name="title"]');
    var coverPathInput = qs('input[name="cover_path"]');
    var csrf = qs('input[name="_csrf_ajax"]') || qs('input[name="_csrf"]');

    if(clearBook && bookInput){
      clearBook.addEventListener('click', function(){
        bookInput.value = '';
      });
    }

    if(clearCover && coverInput){
      clearCover.addEventListener('click', function(){
        coverInput.value = '';
        // If admin had selected a cover upload, revert preview back to cover_path (if any)
        if(coverPathInput && coverPathInput.value){
          setCoverPreview(coverPathInput.value);
        } else {
          setCoverPreview('');
        }
      });
    }

    if(coverInput){
      coverInput.addEventListener('change', function(){
        if(!coverInput.files || !coverInput.files[0]) return;
        var file = coverInput.files[0];
        if(!file.type || file.type.indexOf('image/') !== 0) return;
        var url = URL.createObjectURL(file);
        setCoverPreview(url);
      });
    }

    if(bookInput){
      bookInput.addEventListener('change', function(){
        if(!bookInput.files || !bookInput.files[0]) return;

        var file = bookInput.files[0];
        var title = titleInput ? (titleInput.value || '') : '';
        title = (title || '').trim();
        if(!title && file && file.name){
          title = file.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ').trim();
        }
        var token = csrf ? csrf.value : '';
        if(!token) return;

        var fd = new FormData();
        fd.append('_csrf', token);
        fd.append('title', title);
        fd.append('book_upload', file);

        fetch((window.APP_BASE_PATH || '') + '/admin/cover_detect.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        }).then(function(r){ return r.json(); }).then(function(data){
          if(!data || !data.ok) return;
          if(data.cover_path && coverPathInput){
            coverPathInput.value = data.cover_path;
          }
          if(data.cover_url){
            setCoverPreview(data.cover_url);
          }
        }).catch(function(){
        });
      });
    }

    if(clearBook && bookInput){
      clearBook.addEventListener('click', function(){
        bookInput.value = '';
        // Clearing the book selection should also clear the auto-detected cover path
        // (admin can still type/paste a cover URL manually).
        if(coverPathInput && coverPathInput.value && coverPathInput.value.indexOf('uploads/covers/') === 0){
          coverPathInput.value = '';
        }
        setCoverPreview(coverPathInput && coverPathInput.value ? coverPathInput.value : '');
      });
    }
  }

  var search = document.querySelector('.app-search');
  if(!search) return;

  var onInput = debounce(function(){
    var q = search.value.trim();
    if(q.length === 0) return;
  }, 300);

  search.addEventListener('input', onInput);

  initAdminBookForm();
})();
