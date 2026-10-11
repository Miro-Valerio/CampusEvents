import React from "react";
import { CalendarDays, LogIn, LogOut } from "lucide-react";

export default function AppHeader({
  user,
  activeView,
  onHome,
  onDiscover,
  onMyRsvps,
  onAbout,
  onLogin,
  onLogout,
}) {
  return (
    <header className="topbar">
      <a className="brand" href="#top" onClick={onHome}>
        <span className="brand-icon">
          <CalendarDays size={22} />
        </span>
        <span>
          CampusEvents<span className="brand-accent"> Hub</span>
          <small>SPC CAMPUS LIFE</small>
        </span>
      </a>
      <nav>
        <a
          className={activeView === "discover" ? "active" : ""}
          href="#events"
          onClick={onDiscover}
        >
          Discover events
        </a>
        {user && (
          <a
            className={activeView === "my-rsvps" ? "active" : ""}
            href="#my-rsvps"
            onClick={onMyRsvps}
          >
            My RSVPs
          </a>
        )}
        <a href="#about" onClick={onAbout}>
          About
        </a>
      </nav>
      <div className="account">
        {user ? (
          <>
            <span className="user-name">Hi, {user.name}</span>
            <button
              className="btn btn-outline my-rsvps-shortcut"
              onClick={onMyRsvps}
            >
              My RSVPs
            </button>
            <button className="btn btn-outline" onClick={onLogout}>
              <LogOut size={16} /> Log out
            </button>
          </>
        ) : (
          <button className="btn btn-primary" onClick={onLogin}>
            <LogIn size={16} /> Log in
          </button>
        )}
      </div>
    </header>
  );
}