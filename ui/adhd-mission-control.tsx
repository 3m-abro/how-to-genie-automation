import { useState, useEffect } from 'react';
import { CheckCircle, AlertCircle, Zap, TrendingUp, Clock, Award } from 'lucide-react';

export default function ADHDMissionControl() {
  const [currentFocus, setCurrentFocus] = useState('monitor');
  const [streak, setStreak] = useState(7);
  const [pomodoroActive, setPomodoroActive] = useState(false);
  const [pomodoroTime, setPomodoroTime] = useState(25 * 60);

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

  const systemStatus = {
    overall: 'all_green',
    modules: [
      { name: 'Blog Pipeline', status: 'running', lastRun: '2 hrs ago', nextRun: 'Tomorrow 8 AM' },
      { name: 'Video Creation', status: 'running', lastRun: '4 hrs ago', nextRun: 'Tomorrow 10:30 AM' },
      { name: 'Translations', status: 'running', lastRun: '6 hrs ago', nextRun: 'Tomorrow 2 PM' },
      { name: 'Social Distribution', status: 'running', lastRun: '8 hrs ago', nextRun: 'Tomorrow 10 AM' },
      { name: 'Email Campaigns', status: 'success', lastRun: 'Yesterday', nextRun: 'Next Tuesday' },
    ],
    needsAttention: [],
    todayProgress: 85
  };

  const weeklyWins = [
    { icon: '📝', text: '63 blog posts published (9 languages)', points: 630 },
    { icon: '🎬', text: '63 videos created automatically', points: 630 },
    { icon: '💰', text: 'Revenue: $420 this week (+28%)', points: 420 },
    { icon: '📈', text: '145K page views this week', points: 145 },
    { icon: '🔥', text: '7-day streak maintained!', points: 100 }
  ];

  const priorities = [
    {
      id: 1,
      title: 'System is running perfectly',
      description: 'All 11 modules operational. No action needed.',
      action: 'Just monitor',
      urgency: 'none',
      timeEstimate: '5 min check',
      icon: <CheckCircle className="w-6 h-6 text-green-500" />
    },
    {
      id: 2,
      title: 'Weekly Review (Weekend Task)',
      description: 'Review analytics, approve winning A/B tests, check revenue.',
      action: 'Open weekly dashboard',
      urgency: 'low',
      timeEstimate: '30 minutes',
      icon: <Clock className="w-6 h-6 text-blue-500" />
    },
    {
      id: 3,
      title: 'Optional: Content Ideas Queue',
      description: '47 AI-generated content ideas waiting. Review if you feel like it.',
      action: 'Browse ideas',
      urgency: 'none',
      timeEstimate: '10 minutes',
      icon: <Zap className="w-6 h-6 text-purple-500" />
    }
  ];

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  return (
    <div style={{ background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', minHeight: '100vh', fontFamily: 'Inter, sans-serif', color: '#fff', padding: '24px' }}>
      
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
            : `${systemStatus.needsAttention.length} items need a quick look`}
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
        <h3 style={{ margin: '0 0 20px', fontSize: '20px', fontWeight: '700' }}>⚙️ System Status (11 Modules)</h3>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '12px' }}>
          {systemStatus.modules.map((module, idx) => (
            <div key={idx} style={{ background: 'rgba(255,255,255,0.1)', borderRadius: '12px', padding: '16px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '8px' }}>
                <div style={{ width: '8px', height: '8px', borderRadius: '50%', background: module.status === 'running' ? '#10b981' : '#3b82f6' }}></div>
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
