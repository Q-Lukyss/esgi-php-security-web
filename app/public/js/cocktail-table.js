window.addEventListener("DOMContentLoaded", function () {
    console.log('Cocktail table script loaded.');
  var input = document.getElementById('cocktailSearch');
  var table = document.getElementById('cocktailsTable');
  var length = document.getElementById('cocktailsLength');

  if (!input || !table) return;

  input.addEventListener('input', function () {
    console.log('Filtering cocktails...');
    var q = this.value.trim().toLowerCase();
    var rows = table.tBodies[0].rows;
    for (var i = 0; i < rows.length; i++) {
      var r = rows[i];
      // on concatène quelques colonnes (nom, slug, description)
      var author_name = (r.cells[0]?.innerText || '').toLowerCase();
      var slug = (r.cells[1]?.innerText || '').toLowerCase();
      var name = (r.cells[2]?.innerText || '').toLowerCase();
      var description = (r.cells[3]?.innerText || '').toLowerCase();
      var show = !q || author_name.includes(q) || slug.includes(q) || name.includes(q) || description.includes(q);
      r.style.display = show ? '' : 'none';
      length.innerText = '' + Array.from(rows).filter(r => r.style.display !== 'none').length + ' Potion(s)';
    }
  });
});
