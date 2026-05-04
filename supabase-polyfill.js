// Polyfill for Supabase Client — Fast-Polling with Timestamp Cursor
// Optimized for Localhost / XAMPP.
window.supabaseClientObj = (function() {

    function fetchGet(url) {
        const bust = url.includes('?') ? `&_t=${Date.now()}` : `?_t=${Date.now()}`;
        return fetch(url + bust, { cache: 'no-store' })
            .then(r => r.json())
            .then(d => ({ data: d.data || [], error: d.error || null }));
    }

    function postJSON(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        }).then(r => r.json());
    }

    function makeQueryBuilder(tableName) {
        return {
            select: function(cols) {
                let eqField = null, eqVal = null;
                const builder = {
                    eq: function(field, val) { eqField = field; eqVal = val; return builder; },
                    order: function() { return builder; },
                    limit: function() { return builder; },
                    then: function(resolve, reject) {
                        let url = `api.php?action=get_${tableName}`;
                        if (eqField && eqVal !== null) url += `&${eqField}=${encodeURIComponent(eqVal)}`;
                        fetchGet(url).then(resolve).catch(reject);
                    }
                };
                return builder;
            },
            insert: async function(data) {
                let payload = Array.isArray(data) && data.length === 1 ? data[0] : data;
                let action = `insert_${tableName}`;
                if (Array.isArray(data) && data.length > 1 && tableName === 'buyer_offers') action = 'insert_offers_batch';
                const result = await postJSON(`api.php?action=${action}`, payload);
                return { select: async () => ({ data: result.data || [], error: result.error }), error: result.error, data: result.data };
            },
            update: function(data) {
                return {
                    eq: async function(field, val) {
                        data[field] = val;
                        const action = tableName === 'orders' ? 'update_order_rating' : `update_${tableName}`;
                        const result = await postJSON(`api.php?action=${action}`, data);
                        return { data: result.data || [], error: result.error };
                    }
                };
            },
            delete: function() {
                return {
                    eq: async function(field, val) {
                        let action = `delete_${tableName}`;
                        if (tableName === 'chats' && field === 'buyerId') action = 'delete_chats';
                        if (tableName === 'buyer_offers' && field === 'buyerId') action = 'delete_offers_by_buyer';
                        if (tableName === 'buyer_offers' && field === 'id') action = 'delete_offer';
                        const result = await postJSON(`api.php?action=${action}`, { [field]: val });
                        return { data: result.data || [], error: result.error };
                    }
                };
            }
        };
    }

    const activePollers = {};

    function startPoller(table, filterKey, filterVal, onInsert) {
        const key = `${table}|${filterKey}|${filterVal}`;
        if (activePollers[key]) {
            activePollers[key].callbacks.push(onInsert);
            return key;
        }

        const state = {
            running: true,
            since: null,
            seenIds: new Set(),
            callbacks: [onInsert]
        };
        activePollers[key] = state;

        async function poll() {
            if (!state.running) return;

            try {
                let url = `long_poll.php?table=${encodeURIComponent(table)}`;
                if (filterKey) url += `&filterKey=${encodeURIComponent(filterKey)}&filterVal=${encodeURIComponent(filterVal)}`;
                if (state.since) url += `&since=${encodeURIComponent(state.since)}`;
                url += `&_t=${Date.now()}`;

                const res = await fetch(url, { cache: 'no-store' });
                const json = await res.json();

                // If first call, just establish the 'since' baseline
                if (state.since === null) {
                    state.since = json.since;
                    // Pre-populate seenIds with current IDs to avoid double-processing
                    if (json.data) json.data.forEach(row => state.seenIds.add(row.id));
                } else {
                    // Update cursor
                    if (json.since) state.since = json.since;

                    if (json.data && json.data.length > 0) {
                        for (let row of json.data) {
                            // Deduplicate locally
                            if (!state.seenIds.has(row.id)) {
                                state.seenIds.add(row.id);
                                for (let cb of state.callbacks) {
                                    cb({ eventType: 'INSERT', new: row });
                                }
                            }
                        }
                    }
                }
            } catch(e) {
                console.warn('[Realtime] Sync Error:', e.message);
            }

            if (state.running) setTimeout(poll, 400); // 400ms interval
        }

        poll();
        return key;
    }

    function stopPoller(key, callback) {
        if (!activePollers[key]) return;
        activePollers[key].callbacks = activePollers[key].callbacks.filter(cb => cb !== callback);
        if (activePollers[key].callbacks.length === 0) {
            activePollers[key].running = false;
            delete activePollers[key];
        }
    }

    return {
        from: function(tableName) { return makeQueryBuilder(tableName); },
        channel: function(channelName) {
            let regs = [];
            return {
                on: function(pgEvent, opts, cb) {
                    const filter = opts.filter || '';
                    let fk = '', fv = '';
                    const match = filter.match(/(\w+)=eq\.(.+)/);
                    if (match) { fk = match[1]; fv = match[2]; }
                    if (opts.event === 'INSERT' || pgEvent === 'postgres_changes') {
                        regs.push({ table: opts.table, fk, fv, cb });
                    }
                    return this;
                },
                subscribe: function() {
                    for (let r of regs) {
                        r.key = startPoller(r.table, r.fk, r.fv, r.cb);
                    }
                    return { _regs: regs };
                }
            };
        },
        removeChannel: async function(channelObj) {
            if (channelObj && channelObj._regs) {
                for (let r of channelObj._regs) stopPoller(r.key, r.cb);
            }
        }
    };
})();

window.supabase = { createClient: () => window.supabaseClientObj };
