/* ============ Автосервис CRM — графики и утилиты (без внешних зависимостей) ============ */
(function (global) {
  'use strict';

  var PALETTE = ['#2563eb', '#16a34a', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#db2777', '#65a30d'];

  function fmtMoney(v, short) {
    var n = Number(v || 0);
    if (short && Math.abs(n) >= 1000000) return (n / 1000000).toFixed(1).replace('.0', '') + ' млн';
    if (short && Math.abs(n) >= 10000) return Math.round(n / 1000) + ' тыс';
    return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 }).format(n) + ' ₽';
  }
  function fmtNum(v) {
    return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 1 }).format(Number(v || 0));
  }

  function niceMax(v) {
    if (v <= 0) return 10;
    var exp = Math.pow(10, Math.floor(Math.log10(v)));
    var f = v / exp;
    var step = f <= 1 ? 1 : f <= 2 ? 2 : f <= 2.5 ? 2.5 : f <= 5 ? 5 : 10;
    return step * exp;
  }

  function el(tag, attrs, text) {
    var n = document.createElementNS('http://www.w3.org/2000/svg', tag);
    for (var k in attrs) if (Object.prototype.hasOwnProperty.call(attrs, k)) n.setAttribute(k, attrs[k]);
    if (text != null) n.textContent = text;
    return n;
  }

  /* ---------- Линейный график ---------- */
  function lineChart(target, cfg) {
    var host = typeof target === 'string' ? document.getElementById(target) : target;
    if (!host) return;
    host.innerHTML = '';
    var labels = cfg.labels || [];
    var series = (cfg.series || []).map(function (s, i) {
      return { name: s.name, color: s.color || PALETTE[i % PALETTE.length], data: (s.data || []).map(Number) };
    });
    var W = 720, H = cfg.height || 240;
    var m = { t: 14, r: 16, b: 28, l: 58 };
    var iw = W - m.l - m.r, ih = H - m.t - m.b;

    var maxV = 0;
    series.forEach(function (s) { s.data.forEach(function (v) { if (v > maxV) maxV = v; }); });
    var top = niceMax(maxV * 1.15) || 10;
    var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'chart', preserveAspectRatio: 'none', role: 'img' });

    for (var g = 0; g <= 4; g++) {
      var y = m.t + ih - (ih * g / 4);
      svg.appendChild(el('line', { x1: m.l, y1: y, x2: W - m.r, y2: y, class: 'axis' }));
      svg.appendChild(el('text', { x: m.l - 8, y: y + 3.5, class: 'lbl', 'text-anchor': 'end' }, cfg.money ? fmtMoney(top * g / 4, true) : fmtNum(top * g / 4)));
    }

    var n = labels.length;
    var stepX = n > 1 ? iw / (n - 1) : 0;
    labels.forEach(function (lb, i) {
      if (n > 10 && i % 2) return;
      svg.appendChild(el('text', { x: m.l + i * stepX, y: H - 8, class: 'lbl', 'text-anchor': i === 0 ? 'start' : (i === n - 1 ? 'end' : 'middle') }, lb));
    });

    function pt(i, v) { return [m.l + i * stepX, m.t + ih - (v / top) * ih]; }

    series.forEach(function (s) {
      if (cfg.area !== false && series.length <= 2) {
        var ad = s.data.map(function (v, i) { return pt(i, v)[0] + ',' + pt(i, v)[1]; }).join(' ');
        svg.appendChild(el('polygon', {
          points: m.l + ',' + (m.t + ih) + ' ' + ad + ' ' + (m.l + (n - 1) * stepX) + ',' + (m.t + ih),
          class: 'area', fill: s.color
        }));
      }
      var d = s.data.map(function (v, i) { var p = pt(i, v); return (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join(' ');
      svg.appendChild(el('path', { d: d, class: 'line', stroke: s.color }));
      if (n <= 16) s.data.forEach(function (v, i) {
        var p = pt(i, v);
        svg.appendChild(el('circle', { cx: p[0].toFixed(1), cy: p[1].toFixed(1), r: 3.5, class: 'pt', fill: s.color }));
      });
    });

    host.appendChild(svg);
    if (series.length > 1) {
      var lg = document.createElement('div');
      lg.className = 'legend';
      lg.innerHTML = series.map(function (s) { return '<span><i style="background:' + s.color + '"></i>' + s.name + '</span>'; }).join('');
      host.appendChild(lg);
    }
  }

  /* ---------- Столбчатый график ---------- */
  function barChart(target, cfg) {
    var host = typeof target === 'string' ? document.getElementById(target) : target;
    if (!host) return;
    host.innerHTML = '';
    var labels = cfg.labels || [];
    var data = (cfg.data || []).map(Number);
    var W = 720, H = cfg.height || 240;
    var m = { t: 16, r: 12, b: 30, l: 58 };
    var iw = W - m.l - m.r, ih = H - m.t - m.b;
    var maxV = niceMax(Math.max.apply(null, data.concat([0])) * 1.15) || 10;
    var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'chart', preserveAspectRatio: 'none' });

    for (var g = 0; g <= 4; g++) {
      var y = m.t + ih - (ih * g / 4);
      svg.appendChild(el('line', { x1: m.l, y1: y, x2: W - m.r, y2: y, class: 'axis' }));
      svg.appendChild(el('text', { x: m.l - 8, y: y + 3.5, class: 'lbl', 'text-anchor': 'end' }, cfg.money ? fmtMoney(maxV * g / 4, true) : fmtNum(maxV * g / 4)));
    }
    var slot = iw / Math.max(labels.length, 1);
    var bw = Math.max(6, slot * 0.58);
    data.forEach(function (v, i) {
      var h = (v / maxV) * ih;
      var x = m.l + i * slot + (slot - bw) / 2;
      svg.appendChild(el('rect', { x: x.toFixed(1), y: (m.t + ih - h).toFixed(1), width: bw.toFixed(1), height: Math.max(h, 1).toFixed(1), rx: 4, fill: cfg.color || PALETTE[0] }));
      if (cfg.showValues) svg.appendChild(el('text', { x: (x + bw / 2).toFixed(1), y: (m.t + ih - h - 6).toFixed(1), class: 'val', 'text-anchor': 'middle' }, cfg.money ? fmtMoney(v, true) : fmtNum(v)));
      if (!(labels.length > 12 && i % 2)) svg.appendChild(el('text', { x: (m.l + i * slot + slot / 2).toFixed(1), y: H - 10, class: 'lbl', 'text-anchor': 'middle' }, labels[i]));
    });
    host.appendChild(svg);
  }

  /* ---------- Кольцевая диаграмма ---------- */
  function donutChart(target, cfg) {
    var host = typeof target === 'string' ? document.getElementById(target) : target;
    if (!host) return;
    host.innerHTML = '';
    var slices = (cfg.slices || []).filter(function (s) { return Number(s.value) > 0; });
    var total = slices.reduce(function (a, s) { return a + Number(s.value); }, 0);
    var size = 190, r = 76, thick = 26, cx = size / 2, cy = size / 2;
    var svg = el('svg', { viewBox: '0 0 ' + size + ' ' + size, class: 'chart', style: 'width:190px;height:190px;flex:0 0 190px' });
    if (!total) {
      svg.appendChild(el('circle', { cx: cx, cy: cy, r: r, fill: 'none', stroke: '#e2e8f0', 'stroke-width': thick }));
    } else {
      var acc = -Math.PI / 2;
      slices.forEach(function (s, i) {
        var frac = Number(s.value) / total;
        var ang = frac * Math.PI * 2;
        var x1 = cx + r * Math.cos(acc), y1 = cy + r * Math.sin(acc);
        var x2 = cx + r * Math.cos(acc + ang), y2 = cy + r * Math.sin(acc + ang);
        var large = ang > Math.PI ? 1 : 0;
        svg.appendChild(el('path', {
          d: 'M ' + x1.toFixed(2) + ' ' + y1.toFixed(2) + ' A ' + r + ' ' + r + ' 0 ' + large + ' 1 ' + x2.toFixed(2) + ' ' + y2.toFixed(2),
          fill: 'none', stroke: s.color || PALETTE[i % PALETTE.length], 'stroke-width': thick, 'stroke-linecap': 'butt'
        }));
        acc += ang;
      });
    }
    svg.appendChild(el('text', { x: cx, y: cy - 2, 'text-anchor': 'middle', style: 'font-size:20px;font-weight:700;fill:#0f172a' }, cfg.centerValue != null ? cfg.centerValue : fmtMoney(total, true)));
    svg.appendChild(el('text', { x: cx, y: cy + 16, 'text-anchor': 'middle', style: 'font-size:10px;fill:#94a3b8' }, cfg.centerLabel || 'всего'));

    var wrap = document.createElement('div');
    wrap.style.cssText = 'display:flex;gap:20px;align-items:center;flex-wrap:wrap';
    wrap.appendChild(svg);
    var list = document.createElement('div');
    list.style.cssText = 'flex:1;min-width:180px;display:grid;gap:8px';
    list.innerHTML = slices.length ? slices.map(function (s, i) {
      var pct = total ? (Number(s.value) / total * 100).toFixed(1) : '0';
      var color = s.color || PALETTE[i % PALETTE.length];
      return '<div style="display:grid;grid-template-columns:14px 1fr auto;gap:8px;align-items:center;font-size:12.5px">' +
        '<i style="width:11px;height:11px;border-radius:3px;background:' + color + ';display:inline-block"></i>' +
        '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + s.label + '</span>' +
        '<b style="font-variant-numeric:tabular-nums">' + (cfg.money ? fmtMoney(s.value, true) : fmtNum(s.value)) + ' · ' + pct + '%</b></div>';
    }).join('') : '<div class="muted small">Нет данных за период</div>';
    wrap.appendChild(list);
    host.appendChild(wrap);
  }

  /* ---------- Утилиты ---------- */
  function bindSidebar() {
    var b = document.querySelector('.burger');
    if (b) b.addEventListener('click', function () {
      var s = document.querySelector('.sidebar');
      if (s) s.classList.toggle('open');
    });
  }
  function bindConfirms() {
    document.querySelectorAll('[data-confirm]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        if (!global.confirm(a.getAttribute('data-confirm') || 'Удалить запись?')) e.preventDefault();
      });
    });
  }
  function bindMoneyInputs() {
    document.querySelectorAll('[data-money]').forEach(function (i) {
      i.addEventListener('input', function () {
        var v = i.value.replace(/\D/g, '');
        i.value = v === '' ? '' : Number(v).toLocaleString('ru-RU');
      });
      i.addEventListener('focus', function () { i.value = i.value.replace(/\s/g, ''); });
      i.addEventListener('blur', function () { if (i.value) i.value = Number(i.value.replace(/\D/g, '')).toLocaleString('ru-RU'); });
    });
  }
  function bindAutoSubmit() {
    document.querySelectorAll('[data-autosubmit]').forEach(function (f) {
      f.addEventListener('change', function () { f.submit(); });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindSidebar(); bindConfirms(); bindMoneyInputs(); bindAutoSubmit();
  });

  global.DSH = {
    lineChart: lineChart, barChart: barChart, donutChart: donutChart,
    money: fmtMoney, num: fmtNum, palette: PALETTE
  };
})(window);