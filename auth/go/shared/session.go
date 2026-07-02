package shared

import (
	"crypto/rand"
	"encoding/hex"
	"net/http"
	"sync"
)

// Store es la unica pieza compartida entre las 3 nubes: sesion propia en
// memoria (sin libreria externa), indexada por una cookie "sid". No es
// logica de negocio de ninguna nube, por eso vive en shared/.
type Store struct {
	mu   sync.Mutex
	data map[string]map[string]any
}

func NewStore() *Store {
	return &Store{data: make(map[string]map[string]any)}
}

// Sessions es el store unico del proceso; los 3 paquetes de nube lo usan
// directamente en vez de recibirlo inyectado, para mantener cada handler
// autocontenido (igual de simple que las otras implementaciones del lab).
var Sessions = NewStore()

func RandomID() string {
	b := make([]byte, 16)
	_, _ = rand.Read(b)
	return hex.EncodeToString(b)
}

func (s *Store) GetOrCreate(w http.ResponseWriter, r *http.Request) string {
	cookie, err := r.Cookie("sid")
	if err == nil && cookie.Value != "" {
		s.mu.Lock()
		_, ok := s.data[cookie.Value]
		s.mu.Unlock()
		if ok {
			return cookie.Value
		}
	}
	id := RandomID()
	s.mu.Lock()
	s.data[id] = make(map[string]any)
	s.mu.Unlock()
	http.SetCookie(w, &http.Cookie{Name: "sid", Value: id, Path: "/", HttpOnly: true})
	return id
}

func (s *Store) Get(id, key string) (any, bool) {
	s.mu.Lock()
	defer s.mu.Unlock()
	v, ok := s.data[id][key]
	return v, ok
}

func (s *Store) Set(id, key string, value any) {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.data[id][key] = value
}

func (s *Store) Delete(id, key string) {
	s.mu.Lock()
	defer s.mu.Unlock()
	delete(s.data[id], key)
}
