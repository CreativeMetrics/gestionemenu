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

    /**
     * Da mobile, toccare la riga di un piatto apre un'anteprima rapida (foto/prezzo/allergeni) in
     * basso invece di aprire subito la scheda completa. Da desktop non si attiva: resta il tasto
     * "Apri scheda" normale. Esclude tocchi sulla maniglia di trascinamento e sul segnaposto "no
     * foto" (che deve continuare ad aprire il selettore file, non l'anteprima).
     */
    function initSchedaRapida() {
        var sheet = document.getElementById('scheda-rapida');
        var righe = document.querySelectorAll('.piatto-riga');
        if (!sheet || !righe.length) {
            return;
        }
        var mobile = window.matchMedia('(max-width: 700px)');
        var foto = sheet.querySelector('.scheda-rapida-foto');
        var nome = sheet.querySelector('.scheda-rapida-nome');
        var prezzo = sheet.querySelector('.scheda-rapida-prezzo');
        var desc = sheet.querySelector('.scheda-rapida-desc');
        var allergeni = sheet.querySelector('.scheda-rapida-allergeni');
        var link = sheet.querySelector('.scheda-rapida-link');

        righe.forEach(function (riga) {
            riga.addEventListener('click', function (e) {
                if (!mobile.matches) {
                    return;
                }
                if (e.target.closest('.piatto-maniglia, label, input')) {
                    return;
                }
                e.preventDefault();
                apri(riga);
            });
        });

        sheet.querySelector('.scheda-rapida-sfondo').addEventListener('click', chiudi);
        sheet.querySelector('.scheda-rapida-chiudi').addEventListener('click', chiudi);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !sheet.hidden) {
                chiudi();
            }
        });

        function apri(riga) {
            var img = riga.querySelector('.piatto-thumb');
            if (img) {
                foto.style.backgroundImage = 'url(' + img.src + ')';
                foto.hidden = false;
            } else {
                foto.hidden = true;
            }

            var elNome = riga.querySelector('.nome');
            nome.textContent = elNome ? elNome.textContent : '';

            var elPrezzo = riga.querySelector('.piatto-prezzo');
            prezzo.textContent = elPrezzo ? elPrezzo.textContent : '';

            var elDesc = riga.querySelector('.desc');
            if (elDesc && elDesc.textContent.trim() !== '') {
                desc.textContent = elDesc.textContent;
                desc.hidden = false;
            } else {
                desc.hidden = true;
            }

            allergeni.innerHTML = '';
            riga.querySelectorAll('.chip-allergeni .chip').forEach(function (chip) {
                var span = document.createElement('span');
                span.className = 'chip selezionato';
                span.textContent = chip.textContent;
                allergeni.appendChild(span);
            });

            var elLink = riga.querySelector('.piatto-azioni a');
            link.href = elLink ? elLink.href : '#';

            sheet.hidden = false;
        }

        function chiudi() {
            sheet.hidden = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initRiordinoPiatti();
        initRicercaPiatti();
        initSchedaRapida();
    });
})();
