import { useState, useEffect } from "react";
import { LineChart, Line, BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, AreaChart, Area } from "recharts";

const COLORS = ["#FF6B35","#4ECDC4","#45B7D1","#96CEB4","#FFEAA7","#DDA0DD","#98D8C8","#F7DC6F"];

const API_BASE = (typeof window !== "undefined" && (window as unknown as { API_BASE?: string }).API_BASE) || process.env.REACT_APP_API_URL || "";

type RevenueRow = { month: string; adsense: number; adsterra: number; affiliates: number; total: number; posts: number };
type TrafficRow = { month: string; organic: number; social: number; email: number; referral: number };
type ContentStat = { label: string; value: string; icon: string; change: string; color: string };
type TopPost = { title: string; views: number; revenue: string; network: string };
type AgentRow = { agent: string; runs: number; success: string; avg_time: string };

const emptyRevenue: RevenueRow[] = [];
const emptyTraffic: TrafficRow[] = [];
const emptyContentStats: ContentStat[] = [];
const emptyTopPosts: TopPost[] = [];
const emptyAgentActivity: AgentRow[] = [];
const emptyAffiliate = [{ name: "—", value: 0, revenue: 0, color: "#8888AA" }];
const emptySocial = [{ platform: "—", followers: 0, posts: 0, engagement: "—" }];

export default function Dashboard() {
  const [activeTab, setActiveTab] = useState("overview");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [revenueData, setRevenueData] = useState<RevenueRow[]>(emptyRevenue);
  const [trafficData, setTrafficData] = useState<TrafficRow[]>(emptyTraffic);
  const [contentStats, setContentStats] = useState<ContentStat[]>(emptyContentStats);
  const [agentActivity, setAgentActivity] = useState<AgentRow[]>(emptyAgentActivity);
  const [topPosts, setTopPosts] = useState<TopPost[]>(emptyTopPosts);
  const [affiliateData, setAffiliateData] = useState<{ name: string; value: number; revenue: number; color: string }[]>(emptyAffiliate);
  const [socialData, setSocialData] = useState<{ platform: string; followers: number; posts: number; engagement: string }[]>(emptySocial);

  useEffect(() => {
    const url = `${API_BASE.replace(/\/$/, "")}/api/dashboard/revenue`;
    fetch(url)
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
      })
      .then((data: {
        content_stats?: ContentStat[];
        revenue_data?: RevenueRow[];
        traffic_data?: TrafficRow[];
        agent_activity?: AgentRow[];
        top_posts?: TopPost[];
      }) => {
        setContentStats(Array.isArray(data.content_stats) ? data.content_stats : emptyContentStats);
        setRevenueData(Array.isArray(data.revenue_data) ? data.revenue_data : emptyRevenue);
        setTrafficData(Array.isArray(data.traffic_data) ? data.traffic_data : emptyTraffic);
        setAgentActivity(Array.isArray(data.agent_activity) ? data.agent_activity : emptyAgentActivity);
        setTopPosts(Array.isArray(data.top_posts) ? data.top_posts : emptyTopPosts);
        setAffiliateData(emptyAffiliate);
        setSocialData(emptySocial);
      })
      .catch((err: unknown) => setError(err instanceof Error ? err.message : "Failed to load dashboard"))
      .finally(() => setLoading(false));
  }, []);

  const tabs = [
    { id: "overview", label: "📊 Overview" },
    { id: "revenue", label: "💰 Revenue" },
    { id: "traffic", label: "📈 Traffic" },
    { id: "content", label: "♻️ Content" },
    { id: "agents", label: "🤖 Agents" },
  ];

  return (
    <div style={{ background: "#0F0F1A", minHeight: "100vh", fontFamily: "'Inter', sans-serif", color: "#E8E8F0", padding: "0" }}>
      
      {/* Header */}
      <div style={{ background: "linear-gradient(135deg, #1A1A2E 0%, #16213E 50%, #0F3460 100%)", padding: "24px 32px", borderBottom: "1px solid #2A2A4A" }}>
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          <div style={{ display: "flex", alignItems: "center", gap: "16px" }}>
            <div style={{ fontSize: "40px" }}>🧞‍♂️</div>
            <div>
              <h1 style={{ margin: 0, fontSize: "24px", fontWeight: "800", background: "linear-gradient(135deg, #FF6B35, #4ECDC4)", WebkitBackgroundClip: "text", WebkitTextFillColor: "transparent" }}>
                HowTo-Genie
              </h1>
              <p style={{ margin: 0, fontSize: "12px", color: "#8888AA" }}>v3.0 Revenue & Content Intelligence Dashboard</p>
            </div>
          </div>
          <div style={{ display: "flex", gap: "8px", alignItems: "center" }}>
            <div style={{ width: "8px", height: "8px", borderRadius: "50%", background: "#4ECDC4", boxShadow: "0 0 8px #4ECDC4" }}></div>
            <span style={{ fontSize: "12px", color: "#4ECDC4" }}>All Agents Running</span>
          </div>
        </div>

        {/* Tabs */}
        <div style={{ display: "flex", gap: "8px", marginTop: "20px" }}>
          {tabs.map(t => (
            <button key={t.id} onClick={() => setActiveTab(t.id)}
              style={{ padding: "8px 16px", borderRadius: "8px", border: "none", cursor: "pointer", fontSize: "13px", fontWeight: "600",
                background: activeTab === t.id ? "linear-gradient(135deg, #FF6B35, #FF8C42)" : "rgba(255,255,255,0.05)",
                color: activeTab === t.id ? "#fff" : "#8888AA",
                transition: "all 0.2s" }}>
              {t.label}
            </button>
          ))}
        </div>
      </div>

      <div style={{ padding: "24px 32px" }}>
        {loading && (
          <div style={{ textAlign: "center", padding: "48px", color: "#8888AA" }}>Loading dashboard…</div>
        )}
        {error && (
          <div style={{ background: "rgba(255,107,53,0.1)", border: "1px solid #FF6B35", borderRadius: "12px", padding: "20px", marginBottom: "24px", color: "#FF6B35" }}>
            Could not load data: {error}. Check API base URL and ensure the Laravel revenue API is reachable.
          </div>
        )}
        {!loading && !error && (
        <>
        {/* OVERVIEW TAB */}
        {activeTab === "overview" && (
          <div>
            {/* Stats Grid */}
            <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: "16px", marginBottom: "24px" }}>
              {contentStats.map((s, i) => (
                <div key={i} style={{ background: "linear-gradient(135deg, #1A1A2E, #1E1E35)", borderRadius: "12px", padding: "20px", border: `1px solid ${s.color}22` }}>
                  <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start" }}>
                    <div>
                      <p style={{ margin: "0 0 4px", fontSize: "12px", color: "#8888AA" }}>{s.label}</p>
                      <h2 style={{ margin: "0 0 4px", fontSize: "28px", fontWeight: "800", color: s.color }}>{s.value}</h2>
                      <p style={{ margin: 0, fontSize: "11px", color: "#4ECDC4" }}>{s.change}</p>
                    </div>
                    <span style={{ fontSize: "28px" }}>{s.icon}</span>
                  </div>
                </div>
              ))}
            </div>

            {/* Revenue Chart */}
            <div style={{ background: "#1A1A2E", borderRadius: "12px", padding: "20px", marginBottom: "24px", border: "1px solid #2A2A4A" }}>
              <h3 style={{ margin: "0 0 16px", color: "#E8E8F0", fontSize: "16px" }}>💰 Monthly Revenue Growth</h3>
              <ResponsiveContainer width="100%" height={220}>
                <AreaChart data={revenueData}>
                  <defs>
                    <linearGradient id="totalGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#FF6B35" stopOpacity={0.3}/>
                      <stop offset="95%" stopColor="#FF6B35" stopOpacity={0}/>
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke="#2A2A4A" />
                  <XAxis dataKey="month" stroke="#8888AA" tick={{ fontSize: 12 }} />
                  <YAxis stroke="#8888AA" tick={{ fontSize: 12 }} tickFormatter={v => `$${v}`} />
                  <Tooltip contentStyle={{ background: "#1E1E35", border: "1px solid #2A2A4A", borderRadius: "8px" }} formatter={(v, n) => [`$${v}`, n]} />
                  <Legend />
                  <Area type="monotone" dataKey="total" name="Total Revenue" stroke="#FF6B35" fill="url(#totalGrad)" strokeWidth={2} />
                  <Line type="monotone" dataKey="affiliates" name="Affiliates" stroke="#4ECDC4" strokeWidth={2} dot={false} />
                  <Line type="monotone" dataKey="adsense" name="AdSense" stroke="#45B7D1" strokeWidth={2} dot={false} />
                  <Line type="monotone" dataKey="adsterra" name="Adsterra" stroke="#96CEB4" strokeWidth={2} dot={false} />
                </AreaChart>
              </ResponsiveContainer>
            </div>

            {/* Top Posts */}
            <div style={{ background: "#1A1A2E", borderRadius: "12px", padding: "20px", border: "1px solid #2A2A4A" }}>
              <h3 style={{ margin: "0 0 16px", color: "#E8E8F0", fontSize: "16px" }}>🏆 Top Earning Posts</h3>
              {topPosts.map((p, i) => (
                <div key={i} style={{ display: "flex", justifyContent: "space-between", alignItems: "center", padding: "12px 0", borderBottom: i < topPosts.length - 1 ? "1px solid #2A2A4A" : "none" }}>
                  <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
                    <span style={{ width: "28px", height: "28px", background: `linear-gradient(135deg, ${COLORS[i]}, ${COLORS[i]}88)`, borderRadius: "6px", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "13px", fontWeight: "700" }}>{i+1}</span>
                    <div>
                      <p style={{ margin: 0, fontSize: "13px", color: "#E8E8F0", fontWeight: "500" }}>{p.title}</p>
                      <p style={{ margin: 0, fontSize: "11px", color: "#8888AA" }}>{p.views.toLocaleString()} views · {p.network}</p>
                    </div>
                  </div>
                  <span style={{ fontSize: "16px", fontWeight: "700", color: "#4ECDC4" }}>{p.revenue}</span>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* REVENUE TAB */}
        {activeTab === "revenue" && (
          <div>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "20px" }}>
              <div style={{ background: "#1A1A2E", borderRadius: "12px", padding: "20px", border: "1px solid #2A2A4A" }}>
                <h3 style={{ margin: "0 0 16px", color: "#E8E8F0", fontSize: "16px" }}>📊 Revenue by Source</h3>
                <ResponsiveContainer width="100%" height={250}>
                  <BarChart data={revenueData}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#2A2A4A" />
                    <XAxis dataKey="month" stroke="#8888AA" tick={{ fontSize: 11 }} />
                    <YAxis stroke="#8888AA" tick={{ fontSize: 11 }} tickFormatter={v => `$${v}`} />
                    <Tooltip contentStyle={{ background: "#1E1E35", border: "1px solid #2A2A4A", borderRadius: "8px" }} formatter={(v) => `$${v}`} />
                    <Legend />
                    <Bar dataKey="adsense" name="AdSense" fill="#45B7D1" radius={[4,4,0,0]} />
                    <Bar dataKey="adsterra" name="Adsterra" fill="#96CEB4" radius={[4,4,0,0]} />
                    <Bar dataKey="affiliates" name="Affiliates" fill="#FF6B35" radius={[4,4,0,0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>

              <div style={{ background: "#1A1A2E", borderRadius: "12px", padding: "20px", border: "1px solid #2A2A4A" }}>
                <h3 style={{ margin: "0 0 16px", color: "#E8E8F0", fontSize: "16px" }}>🤝 Affiliate Network Split</h3>
                <ResponsiveContainer width="100%" height={180}>
                  <PieChart>
                    <Pie data={affiliateData} cx="50%" cy="50%" innerRadius={50} outerRadius={80} paddingAngle={4} dataKey="value">
                      {affiliateData.map((e, i) => <Cell key={i} fill={e.color} />)}
                    </Pie>
                    <Tooltip contentStyle={{ background: "#1E1E35", border: "1px solid #2A2A4A", borderRadius: "8px" }} formatter={(v) => `${v}%`} />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "8px", marginTop: "8px" }}>
                  {affiliateData.map((n, i) => (
                    <div key={i} style={{ background: "#0F0F1A", borderRadius: "8px", padding: "10px", border: `1px solid ${n.color}44` }}>
                      <p style={{ margin: 0, fontSize: "11px", color: "#8888AA" }}>{n.name}</p>
                      <p style={{ margin: 0, fontSize: "16px", fontWeight: "700", color: n.color }}>${n.revenue}</p>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div style={{ background: "#1A1A2E", borderRadius: "12px", padding: "20px", marginTop: "20px", border: "1px solid #2A2A4A" }}>
              <h3 style={{ margin: "0 0 4px", color: "#E8E8F0", fontSize: "16px" }}>📈 Revenue Projection (12 Months)</h3>
              <p style={{ margin: "0 0 16px", fontSize: "12px", color: "#8888AA" }}>Based on current growth trajectory</p>
              <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: "12px" }}>
                {[{m:"Month 3",v:"$500",c:"#96CEB4"},{m:"Month 6",v:"$1,655",c:"#4ECDC4"},{m:"Month 9",v:"$4,200",c:"#45B7D1"},{m:"Month 12",v:"$8,500+",c:"#FF6B35"}].map((p,i) => (
                  <div key={i} style={{ textAlign: "center", background: "#0F0F1A", borderRadius: "10px", padding: "16px", border: `1px solid ${p.c}44` }}>
                    <p style={{ margin: "0 0 4px", fontSize: "11px", color: "#8888AA" }}>{p.m}</p>
                    <p style={{ margin: 0, fontSize: "22px", fontWeight: "800", color: p.c }}>{p.v}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* TRAFFIC TAB */}
        {activeTab === "traffic" && (
          <div>
            <div style={{ background: "#1A1A2E", borderRadius: "12px", padding: "20px", marginBottom: "20px", border: "1px solid #2A2A4A" }}>
              <h3 style={{ margin: "0 0 16px", color: "#E8E8F0", fontSize: "16px" }}>📈 Monthly Traffic by Source</h3>
              <ResponsiveContainer width="100%" height={260}>
                <AreaChart data={trafficData}>
                  <defs>
                    {[{k:"organic",c:"#FF6B35"},{k:"social",c:"#4ECDC4"},{k:"email",c:"#45B7D1"},{k:"referral",c:"#96CEB4"}].map(g => (
                      <linearGradient key={g.k} id={`grad_${g.k}`} x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor={g.c} stopOpacity={0.25}/>
                        <stop offset="95%" stopColor={g.c} stopOpacity={0}/>
                      </linearGradient>
                    ))}
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke="#2A2A4A" />
                  <XAxis dataKey="month" stroke="#8888AA" tick={{ fontSize: 12 }} />
                  <YAxis stroke="#8888AA" tick={{ fontSize: 12 }} tickFormatter={v => v >= 1000 ? `${v/1000}K` : v} />
                  <Tooltip contentStyle={{ background: "#1E1E35", border: "1px solid #2A2A4A", borderRadius: "8px" }} formatter={v => v.toLocaleString()} />
                  <Legend />
                  <Area type="monotone" dataKey="organic" name="Organic SEO" stroke="#FF6B35" fill="url(#grad_organic)" strokeWidth={2} />
                  <Area type="monotone" dataKey="social" name="Social Media" stroke="#4ECDC4" fill="url(#grad_social)" strokeWidth={2} />
                  <Area type="monotone" dataKey="email" name="Email" stroke="#45B7D1" fill="url(#grad_email)" strokeWidth={2} />
                  <Area type="monotone" dataKey="referral" name="Referral" stroke="#96CEB4" fill="url(#grad_referral)" strokeWidth={2} />
                </AreaChart>
              </ResponsiveContainer>
            </div>

            <div style={{ background: "#1A1A2E", borderRadius: "12px", padding: "20px", border: "1px solid #2A2A4A" }}>
              <h3 style={{ margin: "0 0 16px", color: "#E8E8F0", fontSize: "16px" }}>📱 Social Media Performance</h3>
              <div style={{ overflowX: "auto" }}>
                <table style={{ width: "100%", borderCollapse: "collapse", fontSize: "13px" }}>
                  <thead>
                    <tr style={{ borderBottom: "1px solid #2A2A4A" }}>
                      {["Platform","Followers","Posts","Engagement","Status"].map(h => (
                        <th key={h} style={{ padding: "10px", textAlign: "left", color: "#8888AA", fontWeight: "600", fontSize: "11px", textTransform: "uppercase" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {socialData.map((r, i) => (
                      <tr key={i} style={{ borderBottom: "1px solid #1E1E35" }}>
                        <td style={{ padding: "12px 10px", fontWeight: "600" }}>{r.platform}</td>
                        <td style={{ padding: "12px 10px", color: "#4ECDC4" }}>{r.followers.toLocaleString()}</td>
                        <td style={{ padding: "12px 10px" }}>{r.posts}</td>
                        <td style={{ padding: "12px 10px" }}>
                          <span style={{ background: parseFloat(r.engagement) > 7 ? "#4ECDC422" : "#FF6B3522", color: parseFloat(r.engagement) > 7 ? "#4ECDC4" : "#FF6B35", padding: "3px 8px", borderRadius: "12px", fontSize: "12px" }}>
                            {r.engagement}
                          </span>
                        </td>
                        <td style={{ padding: "12px 10px" }}><span style={{ color: "#4ECDC4", fontSize: "11px" }}>● Active</span></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}

        {/* CONTENT TAB */}
        {activeTab === "content" && (
          <div>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(5, 1fr)", gap: "12px", marginBottom: "20px" }}>
              {[
                { label: "Blog Posts", value: "181", icon: "📝", sub: "Published" },
                { label: "Videos Made", value: "181", icon: "🎬", sub: "Auto-generated" },
                { label: "Reels/Shorts", value: "362", icon: "🎵", sub: "TikTok + YT" },
                { label: "Email Campaigns", value: "30", icon: "📧", sub: "Sent" },
                { label: "Content Assets", value: "1,810", icon: "♻️", sub: "10x per post" },
              ].map((s, i) => (
                <div key={i} style={{ background: "#1A1A2E", borderRadius: "10px", padding: "16px", border: "1px solid #2A2A4A", textAlign: "center" }}>
                  <div style={{ fontSize: "24px", marginBottom: "6px" }}>{s.icon}</div>
                  <p style={{ margin: "0 0 2px", fontSize: "22px", fontWeight: "800", color: COLORS[i] }}>{s.value}</p>
                  <p style={{ margin: "0 0 2px", fontSize: "12px", color: "#E8E8F0" }}>{s.label}</p>
                  <p style={{ margin: 0, fontSize: "10px", color: "#8888AA" }}>{s.sub}</p>
                </div>
              ))}
            </div>

            <div style={{ background: "#1A1A2E", borderRadius: "12px", padding: "20px", border: "1px solid #2A2A4A" }}>
              <h3 style={{ margin: "0 0 4px", color: "#E8E8F0", fontSize: "16px" }}>♻️ Repurposing Output Per Post</h3>
              <p style={{ margin: "0 0 16px", fontSize: "12px", color: "#8888AA" }}>Every blog post spawns these 10 assets automatically</p>
              <div style={{ display: "grid", gridTemplateColumns: "repeat(2, 1fr)", gap: "10px" }}>
                {[
                  { n: "1", label: "Twitter/X Thread", platform: "Twitter", icon: "🐦", status: "Auto-posted" },
                  { n: "2", label: "LinkedIn Article", platform: "LinkedIn", icon: "💼", status: "Auto-posted" },
                  { n: "3", label: "Newsletter Snippet", platform: "Email", icon: "📧", status: "Weekly digest" },
                  { n: "4", label: "IG Carousel (10 slides)", platform: "Instagram", icon: "📸", status: "Auto-posted" },
                  { n: "5", label: "Pinterest Infographic", platform: "Pinterest", icon: "📌", status: "Auto-posted" },
                  { n: "6", label: "YouTube Short Script", platform: "YouTube", icon: "🎬", status: "Queued" },
                  { n: "7", label: "Podcast Episode Script", platform: "Podcast", icon: "🎙️", status: "Queued" },
                  { n: "8", label: "Quora/Reddit Answers", platform: "Community", icon: "🌐", status: "Queued" },
                  { n: "9", label: "Facebook Group Post", platform: "Facebook", icon: "📘", status: "Auto-posted" },
                  { n: "10", label: "Medium Republication", platform: "Medium", icon: "✍️", status: "Queued" },
                ].map((a, i) => (
                  <div key={i} style={{ display: "flex", alignItems: "center", gap: "12px", background: "#0F0F1A", borderRadius: "8px", padding: "12px", border: "1px solid #2A2A4A" }}>
                    <span style={{ width: "28px", height: "28px", background: `linear-gradient(135deg, ${COLORS[i]}, ${COLORS[i]}88)`, borderRadius: "6px", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "12px", fontWeight: "700", flexShrink: 0 }}>{a.n}</span>
                    <div style={{ flex: 1 }}>
                      <p style={{ margin: "0 0 2px", fontSize: "13px", color: "#E8E8F0", fontWeight: "500" }}>{a.label}</p>
                      <p style={{ margin: 0, fontSize: "11px", color: "#8888AA" }}>{a.platform}</p>
                    </div>
                    <span style={{ fontSize: "10px", padding: "3px 8px", borderRadius: "12px", background: a.status.includes("Auto") ? "#4ECDC422" : "#FF6B3522", color: a.status.includes("Auto") ? "#4ECDC4" : "#FF6B35", whiteSpace: "nowrap" }}>{a.status}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* AGENTS TAB */}
        {activeTab === "agents" && (
          <div>
            <div style={{ background: "#1A1A2E", borderRadius: "12px", padding: "20px", marginBottom: "20px", border: "1px solid #2A2A4A" }}>
              <h3 style={{ margin: "0 0 4px", color: "#E8E8F0", fontSize: "16px" }}>🤖 Agent Performance Monitor</h3>
              <p style={{ margin: "0 0 16px", fontSize: "12px", color: "#8888AA" }}>All agents powered by Ollama (llama3.2) — running locally, zero API cost</p>
              <div style={{ overflowX: "auto" }}>
                <table style={{ width: "100%", borderCollapse: "collapse", fontSize: "13px" }}>
                  <thead>
                    <tr style={{ borderBottom: "1px solid #2A2A4A" }}>
                      {["Agent","Total Runs","Success Rate","Avg Time","Status"].map(h => (
                        <th key={h} style={{ padding: "10px", textAlign: "left", color: "#8888AA", fontWeight: "600", fontSize: "11px", textTransform: "uppercase" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {agentActivity.map((a, i) => (
                      <tr key={i} style={{ borderBottom: "1px solid #1E1E35" }}>
                        <td style={{ padding: "12px 10px", fontWeight: "600" }}>{a.agent}</td>
                        <td style={{ padding: "12px 10px", color: "#4ECDC4" }}>{a.runs}</td>
                        <td style={{ padding: "12px 10px" }}>
                          <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
                            <div style={{ flex: 1, height: "4px", background: "#2A2A4A", borderRadius: "2px", maxWidth: "80px" }}>
                              <div style={{ height: "100%", width: `${parseInt(String(a.success), 10) || 0}%`, background: (parseInt(String(a.success), 10) || 0) >= 98 ? "#4ECDC4" : "#FF6B35", borderRadius: "2px" }}></div>
                            </div>
                            <span style={{ color: parseInt(a.success) >= 98 ? "#4ECDC4" : "#FF6B35", fontSize: "12px" }}>{a.success}</span>
                          </div>
                        </td>
                        <td style={{ padding: "12px 10px", color: "#8888AA" }}>{a.avg_time}</td>
                        <td style={{ padding: "12px 10px" }}><span style={{ color: "#4ECDC4", fontSize: "11px" }}>● Running</span></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>

            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "16px" }}>
              <div style={{ background: "#1A1A2E", borderRadius: "12px", padding: "20px", border: "1px solid #2A2A4A" }}>
                <h3 style={{ margin: "0 0 16px", color: "#E8E8F0", fontSize: "16px" }}>⏱️ Daily Pipeline Schedule</h3>
                {[
                  { time: "8:00 AM", task: "Main Blog Pipeline", color: "#FF6B35" },
                  { time: "10:30 AM", task: "Video Creation Pipeline", color: "#4ECDC4" },
                  { time: "12:00 PM", task: "Content Repurposing", color: "#45B7D1" },
                  { time: "2:00 PM", task: "Comment Moderation #1", color: "#96CEB4" },
                  { time: "4:00 PM", task: "Comment Moderation #2", color: "#96CEB4" },
                  { time: "6:00 PM", task: "Comment Moderation #3", color: "#96CEB4" },
                  { time: "Tues 9AM", task: "Weekly Email Digest", color: "#FFEAA7" },
                ].map((s, i) => (
                  <div key={i} style={{ display: "flex", gap: "12px", marginBottom: "12px", alignItems: "center" }}>
                    <span style={{ fontSize: "11px", color: s.color, fontWeight: "700", minWidth: "85px" }}>{s.time}</span>
                    <div style={{ flex: 1, height: "1px", background: "#2A2A4A" }}></div>
                    <span style={{ fontSize: "12px", color: "#E8E8F0" }}>{s.task}</span>
                  </div>
                ))}
              </div>

              <div style={{ background: "#1A1A2E", borderRadius: "12px", padding: "20px", border: "1px solid #2A2A4A" }}>
                <h3 style={{ margin: "0 0 16px", color: "#E8E8F0", fontSize: "16px" }}>💡 AI Cost Summary</h3>
                {[
                  { item: "Ollama (all 8 agents)", cost: "$0.00", note: "Runs locally" },
                  { item: "Pexels/Pixabay Images", cost: "$0.00", note: "Free API" },
                  { item: "n8n (self-hosted)", cost: "$0.00", note: "Free to self-host" },
                  { item: "Pictory API", cost: "~$19/mo", note: "Video generation" },
                  { item: "Kling AI", cost: "~$10/mo", note: "B-roll generation" },
                  { item: "Banana.dev", cost: "~$5/mo", note: "Thumbnail gen" },
                  { item: "WordPress Hosting", cost: "$10/mo", note: "Shared hosting" },
                  { item: "ConvertKit/MailerLite", cost: "$0–29/mo", note: "Free up to 1K subs" },
                ].map((c, i) => (
                  <div key={i} style={{ display: "flex", justifyContent: "space-between", padding: "8px 0", borderBottom: i < 7 ? "1px solid #2A2A4A" : "none", fontSize: "12px" }}>
                    <div>
                      <span style={{ color: "#E8E8F0" }}>{c.item}</span>
                      <span style={{ color: "#8888AA", marginLeft: "8px", fontSize: "11px" }}>{c.note}</span>
                    </div>
                    <span style={{ color: c.cost === "$0.00" ? "#4ECDC4" : "#FF6B35", fontWeight: "600" }}>{c.cost}</span>
                  </div>
                ))}
                <div style={{ marginTop: "12px", padding: "12px", background: "#0F0F1A", borderRadius: "8px", border: "1px solid #FF6B3544" }}>
                  <p style={{ margin: 0, fontSize: "13px", color: "#FF6B35", fontWeight: "700" }}>Total: ~$44–73/month</p>
                  <p style={{ margin: "4px 0 0", fontSize: "11px", color: "#8888AA" }}>vs estimated Month 6 revenue of $1,655+</p>
                </div>
              </div>
            </div>
          </div>
        )}
        </>
        )}

      </div>
    </div>
  );
}
