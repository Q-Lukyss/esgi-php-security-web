window.addEventListener( "DOMContentLoaded", function () {
  var input = document.getElementById('druideSearch');
  var table = document.getElementById('druideTable');
  var length = document.getElementById('druideLength');
  if (!input || !table) return;

  input.addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    var rows = table.tBodies[0].rows;
    for (var i = 0; i < rows.length; i++) {
      var r = rows[i];
      // on concatène quelques colonnes (nom, type, bio)
      var nom = (r.cells[0]?.innerText || '').toLowerCase();
      var type = (r.cells[1]?.innerText || '').toLowerCase();
      var bio = (r.cells[2]?.innerText || '').toLowerCase();
      var show = !q || nom.includes(q) || type.includes(q) || bio.includes(q);
      r.style.display = show ? '' : 'none';
      length.innerText = '' + Array.from(rows).filter(r => r.style.display !== 'none').length + ' Druides(s)';
    }
  });
})