import React, { useEffect, useState } from "react";
import { createRoot } from "react-dom/client";
import axios from "axios";
import {
  ArrowUpRight,
  CalendarDays,
  RefreshCw,
  Users,
  X,
} from "lucide-react";
import AppHeader from "./components/AppHeader";
import AuthModal from "./components/AuthModal";
import EventDetailsModal from "./components/EventDetailsModal";
import EventDiscoveryView from "./components/EventDiscoveryView";
import MyRsvpsView from "./components/MyRsvpsView";
import OrganizerModal from "./components/OrganizerModal";
import SeatConfirmationModal from "./components/SeatConfirmationModal";
import "./style.css";

const API = "http://127.0.0.1:8000/api";
const http = axios.create({ baseURL: API });
const tokenKey = "ceh_token";
// Attach the saved Sanctum token to protected API requests.
http.interceptors.request.use((config) => {
  const token = localStorage.getItem(tokenKey);
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
const prettyDate = (value) =>
  new Date(value).toLocaleString([], {
    dateStyle: "medium",
    timeStyle: "short",
  });

function App() {
  // Event discovery, filtering, and selected event state.
  const [events, setEvents] = useState([]);
  const [pagination, setPagination] = useState({});
  const [search, setSearch] = useState("");
  const [date, setDate] = useState("");
  const [category, setCategory] = useState("");
  const [organizer, setOrganizer] = useState("");
  const [sort, setSort] = useState("soonest");
  const [selected, setSelected] = useState(null);
  const [organizerPage, setOrganizerPage] = useState(null);

  // Current view and the signed-in user's RSVP state.
  const [activeView, setActiveView] = useState("discover");
  const [myRsvps, setMyRsvps] = useState([]);
  const [rsvpsLoading, setRsvpsLoading] = useState(false);
  const [rsvpBusyEventId, setRsvpBusyEventId] = useState(null);
  const [seatConfirmation, setSeatConfirmation] = useState(null);

  // Login dialog and page-level feedback state.
  const [authOpen, setAuthOpen] = useState(false);
  const [authMode, setAuthMode] = useState("login");
  const [user, setUser] = useState(null);
  const [notice, setNotice] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [authForm, setAuthForm] = useState({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
  });

  // Fetch events using the current search and filter values.
  const loadEvents = async () => {
    setLoading(true);
    setError("");
    try {
      const { data } = await http.get("/events", {
        params: { search, date, category, organizer_id: organizer, sort },
      });
      setEvents(data.data || []);
      setPagination(data);
    } catch {
      setError(
        "We couldn’t load events. Check that the Laravel API is running, then try again.",
      );
    } finally {
      setLoading(false);
    }
  };
  // Restore the saved login and RSVP list after a page refresh.
  useEffect(() => {
    const timer = setTimeout(loadEvents, 250);
    return () => clearTimeout(timer);
  }, [search, date, category, organizer, sort]);
  useEffect(() => {
    if (!localStorage.getItem(tokenKey)) return;
    http
      .get("/user")
      .then(({ data }) => {
        setUser(data);
        return loadMyRsvps();
      })
      .catch(() => {
        localStorage.removeItem(tokenKey);
      });
  }, []);

  // Load event details for the signed-in user's RSVP page.
  const loadMyRsvps = async () => {
    setRsvpsLoading(true);
    try {
      const { data } = await http.get("/my-rsvps");
      setMyRsvps(data.data || []);
    } catch {
      setError("Could not load your RSVPs. Please retry.");
    } finally {
      setRsvpsLoading(false);
    }
  };
  const hasRsvped = (eventId) => myRsvps.some((event) => event.id === eventId);
  const updateRegisteredCount = (eventId, registeredCount) => {
    setEvents((current) =>
      current.map((event) =>
        event.id === eventId
          ? { ...event, registered_count: registeredCount }
          : event,
      ),
    );
    setSelected((current) =>
      current?.id === eventId
        ? { ...current, registered_count: registeredCount }
        : current,
    );
    setMyRsvps((current) =>
      current.map((event) =>
        event.id === eventId
          ? { ...event, registered_count: registeredCount }
          : event,
      ),
    );
  };

  // Create an account or sign in, then load the user's RSVP state.
  const loginOrRegister = async (e) => {
    e.preventDefault();
    setError("");
    setNotice("");
    try {
      const { data } = await http.post(
        authMode === "login" ? "/login" : "/register",
        authForm,
      );
      localStorage.setItem(tokenKey, data.token);
      setUser(data.user);
      setAuthOpen(false);
      setNotice(`Welcome${data.user?.name ? `, ${data.user.name}` : ""}!`);
      await loadMyRsvps();
    } catch (err) {
      setError(
        err.response?.data?.message ||
          Object.values(err.response?.data?.errors || {}).flat()[0] ||
          "Account request failed. Please check your details and retry.",
      );
    }
  };
  const logout = async () => {
    try {
      await http.post("/logout");
    } catch {}
    localStorage.removeItem(tokenKey);
    setUser(null);
    setMyRsvps([]);
    setActiveView("discover");
    setNotice("You have logged out.");
  };
  const openMyRsvps = async (event) => {
    event.preventDefault();
    if (!localStorage.getItem(tokenKey)) {
      setAuthMode("login");
      setAuthOpen(true);
      setError("Log in to view your RSVPs.");
      return;
    }
    setActiveView("my-rsvps");
    setSelected(null);
    setOrganizerPage(null);
    await loadMyRsvps();
  };
  const openEvent = async (event) => {
    setOrganizerPage(null);
    setSelected(event);
    try {
      const { data } = await http.get(`/events/${event.id}`);
      setSelected(data);
    } catch {
      setNotice("Showing event preview. Full details could not be refreshed.");
    }
  };
  const toggleSave = async (event) => {
    if (!localStorage.getItem(tokenKey)) {
      setAuthMode("login");
      setAuthOpen(true);
      setError("Please log in to save an event.");
      return;
    }
    setError("");
    setNotice("");
    try {
      const { data } = await http.post(`/events/${event.id}/save`);
      setNotice(data.message);
    } catch {
      setError(
        "Couldn’t save this event. Your changes may not have been saved. Please try again.",
      );
    }
  };
  // Create or cancel an RSVP and refresh the displayed seat counts.
  const toggleRsvp = async (event) => {
    if (!localStorage.getItem(tokenKey)) {
      setAuthMode("login");
      setAuthOpen(true);
      setError("Log in to RSVP for this event.");
      return;
    }
    setError("");
    setNotice("");
    setRsvpBusyEventId(event.id);
    try {
      const { data } = hasRsvped(event.id)
        ? await http.delete(`/events/${event.id}/rsvp`)
        : await http.post(`/events/${event.id}/rsvp`);
      updateRegisteredCount(event.id, data.registered_count);
      if (data.rsvped)
        setSeatConfirmation({ event, seatNumber: data.seat_number });
      else setNotice(data.message);
      await loadMyRsvps();
    } catch (err) {
      if (err.response?.status === 409)
        setError("This event is full. No seats are available.");
      else
        setError(
          err.response?.data?.message ||
            "Could not update your RSVP. Please try again.",
        );
    } finally {
      setRsvpBusyEventId(null);
    }
  };
  const openOrganizer = async (id) => {
    setSelected(null);
    setOrganizerPage(null);
    try {
      const { data } = await http.get(`/organizers/${id}`);
      setOrganizerPage(data);
    } catch {
      setError("Could not load organizer details. Please retry.");
    }
  };
  const resetFilters = () => {
    setSearch("");
    setDate("");
    setCategory("");
    setOrganizer("");
    setSort("soonest");
  };

  return (
    <div className="app-shell">
      <AppHeader
        user={user}
        activeView={activeView}
        onHome={() => {
          setSelected(null);
          setOrganizerPage(null);
        }}
        onDiscover={() => setActiveView("discover")}
        onMyRsvps={openMyRsvps}
        onAbout={() => setActiveView("discover")}
        onLogin={() => {
          setAuthMode("login");
          setAuthOpen(true);
          setError("");
        }}
        onLogout={logout}
      />
      <main id="top">
        <section className="hero">
          <div className="hero-copy">
            <div className="eyebrow">
              <span className="live-dot" /> YOUR CAMPUS, YOUR MOMENTS
            </div>
            <h1>
              Make campus life
              <br />
              <span>unforgettable.</span>
            </h1>
            <p>
              Discover SPC events, meet your people, and never miss a moment
              that matters.
            </p>
            <a href="#events" className="btn btn-light">
              Explore events <ArrowUpRight size={17} />
            </a>
            <div className="hero-stats">
              <div>
                <b>
                  {events.length}
                  {pagination.total > events.length ? "+" : ""}
                </b>
                <span>Events shown</span>
              </div>
              <div>
                <b>SPC</b>
                <span>One community</span>
              </div>
              <div>
                <b>100%</b>
                <span>Campus spirit</span>
              </div>
            </div>
          </div>
          <div className="hero-art">
            <div className="orb orb-one" />
            <div className="orb orb-two" />
            <div className="floating-card">
              <span className="calendar-mini">
                <CalendarDays size={23} />
              </span>
              <div>
                <b>Something’s happening</b>
                <small>Find your next campus moment</small>
              </div>
              <span className="sparkle">✦</span>
            </div>
            <div className="hero-ticket">
              <span className="ticket-label">UP NEXT AT SPC</span>
              <div className="ticket-icon">
                <Users size={28} />
              </div>
              <b>Find your crowd.</b>
              <span>Events worth showing up for.</span>
              <div className="ticket-bottom">
                <span>Campus · Community · You</span>
                <span>✦ ✦ ✦</span>
              </div>
            </div>
            <div className="tiny-pill">✦ Made for SPC students</div>
          </div>
        </section>
        {notice && (
          <div className="notice" role="status">
            {notice}
            <button onClick={() => setNotice("")} aria-label="Dismiss">
              <X size={15} />
            </button>
          </div>
        )}
        {error && (
          <div className="error-banner" role="alert">
            <span>{error}</span>
            <button
              onClick={() => {
                setError("");
                loadEvents();
              }}
            >
              <RefreshCw size={15} /> Retry
            </button>
            <button
              className="dismiss"
              onClick={() => setError("")}
              aria-label="Dismiss"
            >
              <X size={15} />
            </button>
          </div>
        )}
        {activeView === "discover" && (
          <EventDiscoveryView
            events={events}
            pagination={pagination}
            search={search}
            setSearch={setSearch}
            date={date}
            setDate={setDate}
            category={category}
            setCategory={setCategory}
            organizer={organizer}
            setOrganizer={setOrganizer}
            sort={sort}
            setSort={setSort}
            loading={loading}
            hasRsvped={hasRsvped}
            rsvpBusyEventId={rsvpBusyEventId}
            onRsvp={toggleRsvp}
            onSave={toggleSave}
            onOpen={openEvent}
            onOrganizer={openOrganizer}
            onResetFilters={resetFilters}
            onPage={loadPage}
            prettyDate={prettyDate}
          />
        )}
        {activeView === "my-rsvps" && (
          <MyRsvpsView
            events={myRsvps}
            loading={rsvpsLoading}
            rsvpBusyEventId={rsvpBusyEventId}
            onRefresh={loadMyRsvps}
            onRsvp={toggleRsvp}
            onBrowse={() => setActiveView("discover")}
            prettyDate={prettyDate}
          />
        )}
        <section className="bottom-cta" id="about">
          <div className="cta-icon">
            <CalendarDays size={25} />
          </div>
          <div>
            <h2>There’s more to college than classes.</h2>
            <p>Show up, get involved, and make your SPC story count.</p>
          </div>
          <a href="#events" className="btn btn-light">
            Find an event <ArrowUpRight size={16} />
          </a>
        </section>
      </main>
      <footer>
        <span>© 2026 CampusEvents Hub</span>
        <span>
          Built for the SPC community <b>♥</b>
        </span>
      </footer>

      <EventDetailsModal
        event={selected}
        isRsvped={selected ? hasRsvped(selected.id) : false}
        isBusy={selected ? rsvpBusyEventId === selected.id : false}
        onClose={() => setSelected(null)}
        onRsvp={toggleRsvp}
        onSave={toggleSave}
        onOrganizer={openOrganizer}
        prettyDate={prettyDate}
      />
      <OrganizerModal
        organizer={organizerPage}
        onClose={() => setOrganizerPage(null)}
        onOpenEvent={openEvent}
        prettyDate={prettyDate}
      />
      {authOpen && (
        <AuthModal
          mode={authMode}
          form={authForm}
          onFormChange={(field, value) =>
            setAuthForm((current) => ({ ...current, [field]: value }))
          }
          onModeChange={setAuthMode}
          onSubmit={loginOrRegister}
          onClose={() => setAuthOpen(false)}
          onClearError={() => setError("")}
        />
      )}
      <SeatConfirmationModal
        confirmation={seatConfirmation}
        onClose={() => setSeatConfirmation(null)}
      />
    </div>
  );

  async function loadPage(page) {
    setLoading(true);
    try {
      const { data } = await http.get("/events", {
        params: { search, date, category, organizer_id: organizer, sort, page },
      });
      setEvents(data.data || []);
      setPagination(data);
    } catch {
      setError("Could not change pages. Please retry.");
    } finally {
      setLoading(false);
    }
  }
}

createRoot(document.getElementById("root")).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
);
