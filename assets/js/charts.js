const Charts = {
  colors: {
    gold:    '#D48A1C',
    wine:    '#6A0F1F',
    ember:   '#E4571E',
    green:   '#2D7A4F',
    blue:    '#1E6A9A',
    muted:   '#9A9A9A',
    border:  '#2A2A2A',
    text:    '#F1F1F1',
    bg:      '#1B1B1B',
  },

  
  line(canvasId, labels, datasets, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width  = rect.width  * dpr;
    canvas.height = rect.height * dpr;
    ctx.scale(dpr, dpr);
    const W = rect.width;
    const H = rect.height;
    const pad = { top: 20, right: 20, bottom: 40, left: 60 };
    const chartW = W - pad.left - pad.right;
    const chartH = H - pad.top  - pad.bottom;

    ctx.clearRect(0, 0, W, H);

    let maxVal = 0;
    datasets.forEach(ds => ds.data.forEach(v => { if (v > maxVal) maxVal = v; }));
    if (maxVal === 0) maxVal = 1;
    const nice = Math.ceil(maxVal * 1.15);

    const gridLines = 5;
    ctx.strokeStyle = this.colors.border;
    ctx.lineWidth   = 1;
    ctx.setLineDash([4, 4]);
    for (let i = 0; i <= gridLines; i++) {
      const y = pad.top + chartH - (i / gridLines) * chartH;
      ctx.beginPath();
      ctx.moveTo(pad.left, y);
      ctx.lineTo(pad.left + chartW, y);
      ctx.stroke();

      const val = Math.round((i / gridLines) * nice);
      ctx.fillStyle  = this.colors.muted;
      ctx.font       = '11px Inter, system-ui';
      ctx.textAlign  = 'right';
      ctx.fillText(options.formatY ? options.formatY(val) : val, pad.left - 8, y + 4);
    }
    ctx.setLineDash([]);

    const step = chartW / Math.max(labels.length - 1, 1);
    labels.forEach((lbl, i) => {
      const x = pad.left + i * step;
      ctx.fillStyle = this.colors.muted;
      ctx.font      = '10px Inter, system-ui';
      ctx.textAlign = 'center';
      ctx.fillText(lbl, x, H - 8);
    });

    datasets.forEach((ds, di) => {
      const color = ds.color || (di === 0 ? this.colors.gold : this.colors.wine);
      const data  = ds.data;
      const pts   = data.map((v, i) => ({
        x: pad.left + i * step,
        y: pad.top + chartH - (v / nice) * chartH,
      }));

      if (ds.fill !== false) {
        const grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + chartH);
        grad.addColorStop(0,   color + '30');
        grad.addColorStop(1,   color + '00');
        ctx.beginPath();
        ctx.moveTo(pts[0].x, pad.top + chartH);
        pts.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.lineTo(pts[pts.length - 1].x, pad.top + chartH);
        ctx.closePath();
        ctx.fillStyle = grad;
        ctx.fill();
      }

      ctx.beginPath();
      ctx.strokeStyle = color;
      ctx.lineWidth   = 2.5;
      ctx.lineJoin    = 'round';
      ctx.lineCap     = 'round';
      pts.forEach((p, i) => i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y));
      ctx.stroke();

      pts.forEach(p => {
        ctx.beginPath();
        ctx.arc(p.x, p.y, 4, 0, Math.PI * 2);
        ctx.fillStyle   = color;
        ctx.fill();
        ctx.strokeStyle = this.colors.bg;
        ctx.lineWidth   = 2;
        ctx.stroke();
      });
    });

    if (datasets.length > 1 || datasets[0].label) {
      let lx = pad.left;
      datasets.forEach((ds, di) => {
        const color = ds.color || (di === 0 ? this.colors.gold : this.colors.wine);
        ctx.fillStyle = color;
        ctx.fillRect(lx, 4, 12, 12);
        ctx.fillStyle = this.colors.muted;
        ctx.font      = '11px Inter, system-ui';
        ctx.textAlign = 'left';
        ctx.fillText(ds.label || '', lx + 16, 14);
        lx += 100;
      });
    }
  },

  
  bar(canvasId, labels, datasets, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width  = rect.width  * dpr;
    canvas.height = rect.height * dpr;
    ctx.scale(dpr, dpr);
    const W = rect.width;
    const H = rect.height;
    const pad = { top: 20, right: 16, bottom: 48, left: 60 };
    const chartW = W - pad.left - pad.right;
    const chartH = H - pad.top  - pad.bottom;

    ctx.clearRect(0, 0, W, H);

    let maxVal = 0;
    datasets.forEach(ds => ds.data.forEach(v => { if (v > maxVal) maxVal = v; }));
    if (maxVal === 0) maxVal = 1;
    const nice = Math.ceil(maxVal * 1.15);

    const gridLines = 4;
    for (let i = 0; i <= gridLines; i++) {
      const y = pad.top + chartH - (i / gridLines) * chartH;
      ctx.beginPath();
      ctx.strokeStyle = this.colors.border;
      ctx.lineWidth   = 1;
      ctx.setLineDash([4, 4]);
      ctx.moveTo(pad.left, y);
      ctx.lineTo(pad.left + chartW, y);
      ctx.stroke();
      const val = Math.round((i / gridLines) * nice);
      ctx.setLineDash([]);
      ctx.fillStyle  = this.colors.muted;
      ctx.font       = '11px Inter, system-ui';
      ctx.textAlign  = 'right';
      ctx.fillText(options.formatY ? options.formatY(val) : val, pad.left - 8, y + 4);
    }
    ctx.setLineDash([]);

    const groupW = chartW / labels.length;
    const barW   = Math.max(8, Math.min(groupW * 0.5 / datasets.length, 40));
    const totalW = barW * datasets.length + (datasets.length - 1) * 4;

    labels.forEach((lbl, i) => {
      const cx = pad.left + i * groupW + groupW / 2;
      ctx.fillStyle = this.colors.muted;
      ctx.font      = '10px Inter, system-ui';
      ctx.textAlign = 'center';
      const shortLbl = lbl.length > 10 ? lbl.slice(0, 10) + '…' : lbl;
      ctx.fillText(shortLbl, cx, H - 8);

      datasets.forEach((ds, di) => {
        const color = ds.color || (di === 0 ? this.colors.gold : this.colors.wine);
        const val   = ds.data[i] ?? 0;
        const bH    = (val / nice) * chartH;
        const bx    = cx - totalW / 2 + di * (barW + 4);
        const by    = pad.top + chartH - bH;

        const grad = ctx.createLinearGradient(0, by, 0, by + bH);
        grad.addColorStop(0, color + 'EE');
        grad.addColorStop(1, color + '88');

        const r = Math.min(4, barW / 2);
        ctx.beginPath();
        ctx.moveTo(bx + r, by);
        ctx.lineTo(bx + barW - r, by);
        ctx.quadraticCurveTo(bx + barW, by, bx + barW, by + r);
        ctx.lineTo(bx + barW, by + bH);
        ctx.lineTo(bx, by + bH);
        ctx.lineTo(bx, by + r);
        ctx.quadraticCurveTo(bx, by, bx + r, by);
        ctx.fillStyle = grad;
        ctx.fill();
      });
    });
  },

  
  donut(canvasId, labels, data, colors) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width  = rect.width  * dpr;
    canvas.height = rect.height * dpr;
    ctx.scale(dpr, dpr);
    const W = rect.width;
    const H = rect.height;
    const cx = W / 2;
    const cy = H / 2 - 10;
    const outerR = Math.min(W, H) * 0.35;
    const innerR = outerR * 0.55;

    ctx.clearRect(0, 0, W, H);

    const total = data.reduce((a, b) => a + b, 0);
    if (total === 0) {
      ctx.fillStyle = this.colors.muted;
      ctx.font      = '13px Inter, system-ui';
      ctx.textAlign = 'center';
      ctx.fillText('Sem dados', cx, cy);
      return;
    }

    const palette = colors || [this.colors.gold, this.colors.wine, this.colors.green, this.colors.blue, this.colors.ember];
    let startAngle = -Math.PI / 2;

    data.forEach((val, i) => {
      const slice = (val / total) * Math.PI * 2;
      ctx.beginPath();
      ctx.moveTo(cx, cy);
      ctx.arc(cx, cy, outerR, startAngle, startAngle + slice);
      ctx.closePath();
      ctx.fillStyle = palette[i % palette.length];
      ctx.fill();
      ctx.strokeStyle = '#141414';
      ctx.lineWidth   = 2;
      ctx.stroke();
      startAngle += slice;
    });

    ctx.beginPath();
    ctx.arc(cx, cy, innerR, 0, Math.PI * 2);
    ctx.fillStyle = this.colors.bg;
    ctx.fill();

    ctx.fillStyle = this.colors.text;
    ctx.font      = 'bold 14px Inter, system-ui';
    ctx.textAlign = 'center';
    ctx.fillText('Total', cx, cy - 4);
    ctx.fillStyle = this.colors.gold;
    ctx.font      = 'bold 18px Inter, system-ui';
    ctx.fillText(total.toLocaleString('pt-BR'), cx, cy + 18);

    const lTop = cy + outerR + 16;
    const colW  = W / Math.ceil(labels.length / 2);
    labels.forEach((lbl, i) => {
      const row = Math.floor(i / 2);
      const col = i % 2;
      const lx  = col * colW + 20;
      const ly  = lTop + row * 22;
      ctx.fillStyle = palette[i % palette.length];
      ctx.fillRect(lx, ly, 10, 10);
      ctx.fillStyle = this.colors.muted;
      ctx.font      = '11px Inter, system-ui';
      ctx.textAlign = 'left';
      ctx.fillText(`${lbl} (${data[i]})`, lx + 14, ly + 10);
    });
  },
};
