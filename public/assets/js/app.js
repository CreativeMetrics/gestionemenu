(function () {
    'use strict';

    function csrfToken() {
        var el = document.getElementById('portate-container');
        return el ? el.dataset.csrf : '';
    }

    function inviaJson(url, corpo) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(corpo),
        }).then(function (r) { return r.json(); });
    }

    function initRiordinoPiatti() {
        var liste = document.querySelectorAll('.lista-piatti');
        if (!liste.length || typeof Sortable === 'undefined') {
            return;
        }
        var listeSortable = [];
        liste.forEach(function (el) {
            var s = Sortable.create(el, {
                group: 'piatti',
                handle: '.piatto-maniglia',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: salvaRiordinoPiatti,
            });
            listeSortable.push(s);
        });

        function salvaRiordinoPiatti() {
            var payload = { csrf_token: csrfToken(), liste: [] };
            document.querySelectorAll('.lista-piatti').forEach(function (el) {
                var ids = Array.prototype.map.call(
                    el.querySelectorAll('.piatto-riga'),
                    function (r) { return parseInt(r.dataset.piattoId, 10); }
                );
                payload.liste.push({ portata_id: parseInt(el.dataset.portataId, 10), ids: ids });
            });
            inviaJson('/piatti/riordina', payload);
        }
    }

    /** Filtra i piatti visibili mentre si digita, cercando in nome/descrizione/prezzo/allergeni. */
    function initRicercaPiatti() {
        var input = document.getElementById('ricerca-piatti');
        if (!input) {
            return;
        }
        var risultatiVuoti = document.getElementById('ricerca-nessun-risultato');

        input.addEventListener('input', function () {
            var termine = input.value.trim().toLowerCase();
            var trovatiTotale = 0;

            document.querySelectorAll('.portata-blocco').forEach(function (blocco) {
                var trovatiInBlocco = 0;
                blocco.querySelectorAll('.piatto-riga').forEach(function (riga) {
                    if (riga.dataset.ricerca === undefined) {
                        riga.dataset.ricerca = riga.textContent.toLowerCase();
                    }
                    var match = termine === '' || riga.dataset.ricerca.indexOf(termine) !== -1;
                    riga.style.display = match ? '' : 'none';
                    if (match) {
                        trovatiInBlocco++;
                    }
                });
                blocco.style.display = (termine === '' || trovatiInBlocco > 0) ? '' : 'none';
                trovatiTotale += trovatiInBlocco;
            });

            if (risultatiVuoti) {
                risultatiVuoti.style.display = (termine !== '' && trovatiTotale === 0) ? 'block' : 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initRiordinoPiatti();
        initRicercaPiatti();
    });
})();
