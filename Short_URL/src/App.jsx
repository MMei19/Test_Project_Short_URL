import { useEffect, useRef, useState } from "react";
import { QRCodeCanvas } from "qrcode.react";
import {
  FiBarChart2,
  FiCheck,
  FiClock,
  FiCopy,
  FiExternalLink,
  FiLink2,
  FiPlusSquare,
  FiTrendingUp,
} from "react-icons/fi";
import linkImage from "./assets/link.png";
import "./App.css";

const API = "/api";

async function fetchLinks() {
  const response = await fetch(`${API}/links_URL/read.php`, { cache: "no-store" });
  const body = await response.json();
  if (!response.ok) throw new Error(body.message || "โหลดข้อมูลไม่สำเร็จ");
  return body.data;
}

function App() {
  const [url, setUrl] = useState("");
  const [alias, setAlias] = useState("");
  const [expiry, setExpiry] = useState("never");
  const [result, setResult] = useState(null);
  const [links, setLinks] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [copied, setCopied] = useState(false);
  const qrRef = useRef(null);
  const totalClicks = links.reduce(
    (sum, link) => sum + Number(link.clicks || 0),
    0,
  );
  const averageClicks = links.length
    ? (totalClicks / links.length).toFixed(1).replace(".0", "")
    : 0;
  const today = new Date().toISOString().slice(0, 10);
  const activeLinks = links.filter(
    (link) =>
      link.status === "active" &&
      (!link.expires_at || link.expires_at >= today),
  ).length;

  useEffect(() => {
    if (window.location.hash) {
      window.history.replaceState(
        null,
        "",
        `${window.location.pathname}${window.location.search}`,
      );
      window.scrollTo({ top: 0, behavior: "auto" });
    }
    let active = true;
    const refresh = () => {
      fetchLinks().then((data) => { if (active) setLinks(data); }).catch(() => {});
    };
    const firstRefresh = window.setTimeout(refresh, 0);
    const interval = window.setInterval(refresh, 2000);
    const refreshWhenVisible = () => { if (!document.hidden) refresh(); };
    window.addEventListener("focus", refresh);
    document.addEventListener("visibilitychange", refreshWhenVisible);
    return () => {
      active = false;
      window.clearTimeout(firstRefresh);
      window.clearInterval(interval);
      window.removeEventListener("focus", refresh);
      document.removeEventListener("visibilitychange", refreshWhenVisible);
    };
  }, []);

  const createLink = async (event) => {
    event.preventDefault();
    setError("");
    setResult(null);
    setLoading(true);
    try {
      const response = await fetch(`${API}/links_URL/create.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          original_url: url.trim(),
          short_code: alias.trim() || null,
          expiry,
        }),
      });
      const body = await response.json();
      if (!response.ok) throw new Error(body.message || "สร้างลิงก์ไม่สำเร็จ");
      setResult(body.data);
      setAlias("");
      setLinks(await fetchLinks());
    } catch (requestError) {
      setError(
        requestError.message === "Failed to fetch"
          ? "เชื่อมต่อ API ไม่ได้"
          : requestError.message,
      );
    } finally {
      setLoading(false);
    }
  };

  const copyLink = async () => {
    await navigator.clipboard.writeText(result.short_url);
    setCopied(true);
    setTimeout(() => setCopied(false), 1600);
  };

  const downloadQr = () => {
    const canvas = qrRef.current?.querySelector("canvas");
    if (!canvas) return;
    const anchor = document.createElement("a");
    anchor.download = `qr-${result.short_code}.png`;
    anchor.href = canvas.toDataURL("image/png");
    anchor.click();
  };

  return (
    <div className="site-shell">
      <header className="topbar">
        <nav className="nav-wrap">
          <a className="logo" href="/">
            Short URL
          </a>
        </nav>
      </header>

      <main>
        <section className="hero" id="top">
          <div className="hero-copy">
            <h1>ย่อ URL ของคุณได้ง่าย ๆ</h1>
            <p>บริการย่อ URL ที่รวดเร็วและง่ายต่อการใช้งาน</p>
          </div>
          <img src={linkImage} alt="สัญลักษณ์ลิงก์" />
        </section>

        <section className="creator-card">
          <div className="creator-heading">
            <FiPlusSquare />
            <div>
              <h2>สร้างลิงก์ของคุณ</h2>
              <p>สร้างลิงก์ย่อของคุณได้ง่าย ๆ เพียงไม่กี่ขั้นตอน</p>
            </div>
          </div>
          <form onSubmit={createLink}>
            <label htmlFor="url">ลิงก์ปลายทาง:</label>
            <div className="url-row">
              <input
                id="url"
                type="url"
                required
                value={url}
                onChange={(event) => setUrl(event.target.value)}
                placeholder="วางลิงก์ของคุณที่นี่"
              />
              <button disabled={loading}>
                {loading ? "กำลังสร้าง…" : "สร้างลิงก์"}
              </button>
            </div>
            <div className="option-row">
              <div>
                <label htmlFor="alias">นามแฝง (ไม่จำเป็น):</label>
                <div className="alias-field">
                  <span>https://short-url.lnw.mn/</span>
                  <input
                    id="alias"
                    value={alias}
                    onChange={(event) => setAlias(event.target.value)}
                    pattern="[A-Za-z0-9_-]{3,32}"
                    maxLength="32"
                    placeholder="วางนามแฝงของคุณที่นี่"
                  />
                </div>
              </div>
              <div>
                <label htmlFor="expiry">วันหมดอายุ (ไม่จำเป็น):</label>
                <select
                  id="expiry"
                  value={expiry}
                  onChange={(event) => setExpiry(event.target.value)}
                >
                  <option value="never">ไม่มีวันหมดอายุ</option>
                  <option value="7">7 วัน</option>
                  <option value="30">30 วัน</option>
                  <option value="365">1 ปี</option>
                </select>
              </div>
            </div>
            {error && (
              <div className="error" role="alert">
                {error}
              </div>
            )}
          </form>
        </section>

        {result && (
          <section className="result-card">
            <div className="success">
              <FiCheck /> สร้างและบันทึกลิงก์แล้ว
            </div>
            <div className="result-grid">
              <div className="result-info">
                <small>SHORT URL ของคุณ</small>
                <a href={result.short_url} target="_blank" rel="noreferrer">
                  {result.short_url} <FiExternalLink />
                </a>
                <p>ปลายทาง: {result.original_url}</p>
                <div className="actions">
                  <button type="button" onClick={copyLink}>
                    <FiCopy /> {copied ? "คัดลอกแล้ว" : "คัดลอกลิงก์"}
                  </button>
                  <button
                    type="button"
                    className="secondary"
                    onClick={downloadQr}
                  >
                    ดาวน์โหลด QR
                  </button>
                </div>
              </div>
              <div className="qr" ref={qrRef}>
                <QRCodeCanvas
                  value={result.short_url}
                  size={160}
                  level="H"
                  marginSize={2}
                />
                <span>สแกนเพื่อเปิดลิงก์</span>
              </div>
            </div>
          </section>
        )}

        <section className="summary-grid" id="stats" aria-label="สถิติลิงก์">
          <article className="summary-card teal-card">
            <div className="summary-icon"><FiLink2 /></div>
            <div><p>ลิงก์ทั้งหมด</p><strong>{links.length}</strong></div>
          </article>
          <article className="summary-card blue-card">
            <div className="summary-icon"><FiBarChart2 /></div>
            <div><p>ยอดคลิกทั้งหมด</p><strong>{totalClicks}</strong><small>ข้อมูลการใช้งานจริง</small></div>
          </article>
          <article className="summary-card orange-card">
            <div className="summary-icon"><FiTrendingUp /></div>
            <div><p>อัตราคลิกเฉลี่ย</p><strong>{averageClicks}</strong><small>คลิกต่อลิงก์</small></div>
          </article>
          <article className="summary-card purple-card">
            <div className="summary-icon"><FiClock /></div>
            <div><p>ลิงก์ที่ใช้งาน</p><strong>{activeLinks}</strong><small>จากทั้งหมด {links.length} ลิงก์</small></div>
          </article>
        </section>

        <section className="recent" id="recent">
          <div className="section-title">
            <div>
              <h2>ลิงก์ที่สร้างล่าสุด</h2>
            </div>
              <div className="live-status"><i /> อัปเดตเรียลไทม์</div>
              <span>{links.length} รายการ</span>
          </div>
          {links.length === 0 ? (
            <div className="empty">ยังไม่มีลิงก์ ลองสร้างรายการแรกด้านบน</div>
          ) : (
            <div className="link-list">
              {links.slice(0, 8).map((link) => (
                <div className="link-item" key={link.link_id}>
                  <div className="link-icon">
                    <FiLink2 />
                  </div>
                  <div className="link-meta">
                    <a href={link.short_url} target="_blank" rel="noreferrer">
                      {link.short_url}
                    </a>
                    <p>{link.original_url}</p>
                  </div>
                  <div className="stats">
                    <strong>{link.clicks}</strong>
                    <small>คลิก</small>
                  </div>
                </div>
              ))}
            </div>
          )}
        </section>
      </main>
      <footer>Short URL · บริการย่อลิงก์พร้อม QR Code</footer>
    </div>
  );
}

export default App;
