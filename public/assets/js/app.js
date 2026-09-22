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

    document.addEventListener('DOMContentLoaded', function () {
        initRiordinoPiatti();
    });
})();
