import { useState, useEffect } from 'react';
import { CheckCircle, AlertCircle, Zap, TrendingUp, Clock, Award } from 'lucide-react';

const API_BASE = (typeof process !== 'undefined' && process.env?.REACT_APP_MISSION_CONTROL_API) ||
  (typeof import.meta !== 'undefined' && (import.meta as { env?: { VITE_MISSION_CONTROL_API?: string } }).env?.VITE_MISSION_CONTROL_API) ||
  '';

const defaultSystemStatus = {
  overall: 'all_green' as const,
  modules: [] as { name: string; status: string; lastRun: string; nextRun: string }[],
  needsAttention: [] as string[],
  todayProgress: 0,
};

export default function ADHDMissionControl() {
  const [currentFocus, setCurrentFocus] = useState('monitor');
  const [streak, setStreak] = useState(0);
  const [pomodoroActive, setPomodoroActive] = useState(false);
  const [pomodoroTime, setPomodoroTime] = useState(25 * 60);
  const [systemStatus, setSystemStatus] = useState(defaultSystemStatus);
  const [weeklyWins, setWeeklyWins] = useState<{ icon: string; text: string; points: number }[]>([]);
  const [priorities, setPriorities] = useState<{
    id: number;
    title: string;
    description: string;
    action: string;
    urgency: string;
    timeEstimate: string;
    icon: React.ReactNode;
  }[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const url = `${API_BASE}/api/n8n/status`.replace(/\/+/g, '/');
    fetch(url)
      .then((res) => (res.ok ? res.json() : Promise.reject(new Error(`HTTP ${res.status}`))))
      .then((data) => {
        const ss = data.system_status || {};
        setSystemStatus({
          overall: ss.overall || 'all_green',
          modules: (ss.modules || []).map((m: { name?: string; status?: string; last_run?: string; next_run?: string }) => ({
            name: m.name ?? 'Unknown',
            status: m.status ?? 'stopped',
            lastRun: m.last_run ?? 'N/A',
            nextRun: m.next_run ?? 'Scheduled',
          })),
          needsAttention: ss.needsAttention ?? [],
          todayProgress: typeof data.today_progress === 'number' ? data.today_progress : (ss.todayProgress ?? 0),
        });
        setStreak(typeof data.streak === 'number' ? data.streak : 0);
        setWeeklyWins(Array.isArray(data.weekly_wins) ? data.weekly_wins : []);
        const rawPriorities = Array.isArray(data.priorities) ? data.priorities : [];
        const iconByUrgency = (u: string) => {
          if (u === 'high') return <AlertCircle className="w-6 h-6 text-amber-500" />;
          if (u === 'low') return <Clock className="w-6 h-6 text-blue-500" />;
          return <CheckCircle className="w-6 h-6 text-green-500" />;
        };
        setPriorities(
          rawPriorities.slice(0, 5).map((p: { title?: string; description?: string; action?: string; urgency?: string; time_estimate?: string }, i: number) => ({
            id: i + 1,
            title: p.title ?? '',
            description: p.description ?? '',
            action: p.action ?? '',
            urgency: p.urgency ?? 'none',
            timeEstimate: p.time_estimate ?? '',
            icon: iconByUrgency(p.urgency ?? 'none'),
          }))
        );
        setError(null);
      })
      .catch((err) => {
        setError(err?.message ?? 'Failed to load mission control data');
        setSystemStatus(defaultSystemStatus);
        setWeeklyWins([]);
        setPriorities([]);
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (!pomodoroActive) return;
    const timer = setInterval(() => {
      setPomodoroTime(prev => {
        if (prev <= 0) {
          setPomodoroActive(false);
          return 25 * 60;
        }
        return prev - 1;
      });
    }, 1000);
    return () => clearInterval(timer);
  }, [pomodoroActive]);

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  if (loading) {
    return (
      <div style={{ background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', minHeight: '100vh', fontFamily: 'Inter, sans-serif', color: '#fff', padding: '24px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
        <p style={{ fontSize: '18px', opacity: 0.9 }}>Loading mission control…</p>
      </div>
    );
  }

  const needsAttentionCount = Array.isArray(systemStatus.needsAttention) ? systemStatus.needsAttention.length : 0;

  return (
    <div style={{ background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', minHeight: '100vh', fontFamily: 'Inter, sans-serif', color: '#fff', padding: '24px' }}>
      {error && (
        <div style={{ background: 'rgba(239,68,68,0.2)', border: '1px solid #ef4444', borderRadius: '12px', padding: '16px', marginBottom: '24px' }}>
          <p style={{ margin: 0, fontSize: '14px' }}>⚠️ {error}</p>
        </div>
      )}
      
      {/* Header */}
      <div style={{ background: 'rgba(255,255,255,0.15)', backdropFilter: 'blur(10px)', borderRadius: '16px', padding: '24px', marginBottom: '24px', border: '1px solid rgba(255,255,255,0.2)' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div>
            <h1 style={{ margin: 0, fontSize: '28px', fontWeight: '800' }}>🧞‍♂️ HowTo-Genie Mission Control</h1>
            <p style={{ margin: '4px 0 0', fontSize: '14px', opacity: 0.9 }}>ADHD-Optimized Interface • Everything Automated • Zero Stress</p>
          </div>
          <div style={{ textAlign: 'right' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px', background: 'rgba(255,255,255,0.2)', padding: '12px 20px', borderRadius: '12px' }}>
              <Award className="w-6 h-6 text-yellow-300" />
              <div>
                <div style={{ fontSize: '24px', fontWeight: '800' }}>{streak} Days</div>
                <div style={{ fontSize: '11px', opacity: 0.9 }}>Streak 🔥</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Big Status Indicator */}
      <div style={{ background: systemStatus.overall === 'all_green' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #f59e0b, #d97706)', borderRadius: '16px', padding: '32px', marginBottom: '24px', textAlign: 'center', border: '1px solid rgba(255,255,255,0.3)' }}>
        <div style={{ fontSize: '48px', marginBottom: '8px' }}>
          {systemStatus.overall === 'all_green' ? '✅' : '⚠️'}
        </div>
        <h2 style={{ margin: '0 0 8px', fontSize: '32px', fontWeight: '800' }}>
          {systemStatus.overall === 'all_green' ? 'Everything is Working Perfectly' : 'Needs Your Attention'}
        </h2>
        <p style={{ margin: 0, fontSize: '16px', opacity: 0.9 }}>
          {systemStatus.overall === 'all_green' 
            ? 'All systems operational. You can relax. Check back next week.' 
            : `${needsAttentionCount} items need a quick look`}
        </p>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '24px', marginBottom: '24px' }}>
        
        {/* Today's Progress */}
        <div style={{ background: 'rgba(255,255,255,0.15)', backdropFilter: 'blur(10px)', borderRadius: '16px', padding: '24px', border: '1px solid rgba(255,255,255,0.2)' }}>
          <h3 style={{ margin: '0 0 16px', fontSize: '18px', fontWeight: '700', display: 'flex', alignItems: 'center', gap: '8px' }}>
            <TrendingUp className="w-5 h-5" />
            Today's Automation Progress
          </h3>
          <div style={{ position: 'relative', width: '100%', height: '12px', background: 'rgba(255,255,255,0.2)', borderRadius: '12px', overflow: 'hidden' }}>
            <div style={{ position: 'absolute', left: 0, top: 0, height: '100%', width: `${systemStatus.todayProgress}%`, background: 'linear-gradient(90deg, #10b981, #34d399)', borderRadius: '12px', transition: 'width 0.5s' }}></div>
          </div>
          <p style={{ margin: '12px 0 0', fontSize: '14px', opacity: 0.9 }}>{systemStatus.todayProgress}% complete • All tasks on schedule</p>
        </div>

        {/* Pomodoro Timer */}
        <div style={{ background: 'rgba(255,255,255,0.15)', backdropFilter: 'blur(10px)', borderRadius: '16px', padding: '24px', border: '1px solid rgba(255,255,255,0.2)' }}>
          <h3 style={{ margin: '0 0 16px', fontSize: '18px', fontWeight: '700' }}>🍅 Focus Timer (Optional)</h3>
          <div style={{ fontSize: '36px', fontWeight: '800', textAlign: 'center', margin: '8px 0' }}>
            {formatTime(pomodoroTime)}
          </div>
          <button 
            onClick={() => setPomodoroActive(!pomodoroActive)}
            style={{ width: '100%', padding: '12px', background: pomodoroActive ? '#ef4444' : '#10b981', border: 'none', borderRadius: '8px', color: '#fff', fontSize: '14px', fontWeight: '600', cursor: 'pointer' }}>
            {pomodoroActive ? 'Stop' : 'Start 25 Min Focus'}
          </button>
        </div>
      </div>

      {/* Priority Actions */}
      <div style={{ background: 'rgba(255,255,255,0.15)', backdropFilter: 'blur(10px)', borderRadius: '16px', padding: '24px', marginBottom: '24px', border: '1px solid rgba(255,255,255,0.2)' }}>
        <h3 style={{ margin: '0 0 20px', fontSize: '20px', fontWeight: '700' }}>🎯 What To Do Next (Prioritized for You)</h3>
        {priorities.map((priority, idx) => (
          <div key={priority.id} style={{ background: 'rgba(255,255,255,0.1)', borderRadius: '12px', padding: '20px', marginBottom: '16px', border: priority.urgency === 'high' ? '2px solid #f59e0b' : '1px solid rgba(255,255,255,0.2)' }}>
            <div style={{ display: 'flex', gap: '16px', alignItems: 'flex-start' }}>
              <div style={{ flexShrink: 0 }}>{priority.icon}</div>
              <div style={{ flex: 1 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '8px' }}>
                  <h4 style={{ margin: 0, fontSize: '16px', fontWeight: '600' }}>{priority.title}</h4>
                  <span style={{ fontSize: '12px', background: priority.urgency === 'high' ? '#f59e0b' : priority.urgency === 'low' ? '#3b82f6' : '#6b7280', padding: '4px 12px', borderRadius: '12px', whiteSpace: 'nowrap' }}>
                    {priority.timeEstimate}
                  </span>
                </div>
                <p style={{ margin: '0 0 12px', fontSize: '14px', opacity: 0.9 }}>{priority.description}</p>
                <button style={{ padding: '8px 16px', background: 'linear-gradient(135deg, #10b981, #059669)', border: 'none', borderRadius: '8px', color: '#fff', fontSize: '13px', fontWeight: '600', cursor: 'pointer' }}>
                  {priority.action}
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Weekly Wins */}
      <div style={{ background: 'rgba(255,255,255,0.15)', backdropFilter: 'blur(10px)', borderRadius: '16px', padding: '24px', marginBottom: '24px', border: '1px solid rgba(255,255,255,0.2)' }}>
        <h3 style={{ margin: '0 0 20px', fontSize: '20px', fontWeight: '700' }}>🏆 This Week's Wins (Celebrate!)</h3>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '12px' }}>
          {weeklyWins.map((win, idx) => (
            <div key={idx} style={{ background: 'rgba(255,255,255,0.1)', borderRadius: '12px', padding: '16px', display: 'flex', alignItems: 'center', gap: '12px' }}>
              <span style={{ fontSize: '32px' }}>{win.icon}</span>
              <div style={{ flex: 1 }}>
                <p style={{ margin: '0 0 4px', fontSize: '13px', fontWeight: '600' }}>{win.text}</p>
                <p style={{ margin: 0, fontSize: '12px', opacity: 0.8 }}>+{win.points} XP</p>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Module Status Grid */}
      <div style={{ background: 'rgba(255,255,255,0.15)', backdropFilter: 'blur(10px)', borderRadius: '16px', padding: '24px', border: '1px solid rgba(255,255,255,0.2)' }}>
        <h3 style={{ margin: '0 0 20px', fontSize: '20px', fontWeight: '700' }}>⚙️ System Status ({systemStatus.modules.length} Modules)</h3>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '12px' }}>
          {systemStatus.modules.map((module, idx) => (
            <div key={idx} style={{ background: 'rgba(255,255,255,0.1)', borderRadius: '12px', padding: '16px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '8px' }}>
                <div style={{ width: '8px', height: '8px', borderRadius: '50%', background: module.status === 'error' ? '#ef4444' : module.status === 'running' ? '#10b981' : '#3b82f6' }}></div>
                <h4 style={{ margin: 0, fontSize: '14px', fontWeight: '600' }}>{module.name}</h4>
              </div>
              <p style={{ margin: '0 0 4px', fontSize: '12px', opacity: 0.8 }}>Last: {module.lastRun}</p>
              <p style={{ margin: 0, fontSize: '11px', opacity: 0.7 }}>Next: {module.nextRun}</p>
            </div>
          ))}
        </div>
      </div>

      {/* ADHD Tips */}
      <div style={{ marginTop: '24px', background: 'rgba(255,255,255,0.1)', backdropFilter: 'blur(10px)', borderRadius: '16px', padding: '20px', border: '1px solid rgba(255,255,255,0.2)' }}>
        <p style={{ margin: 0, fontSize: '14px', opacity: 0.9 }}>
          💡 <strong>ADHD Tip:</strong> The system is designed to run itself. You only need to check this dashboard once per week on weekends. Everything else is automated. No daily tasks. No guilt. No overwhelm.
        </p>
      </div>

    </div>
  );
}
