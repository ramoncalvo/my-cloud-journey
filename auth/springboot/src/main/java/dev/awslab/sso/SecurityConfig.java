package dev.awslab.sso;

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.oauth2.core.user.OAuth2User;
import org.springframework.security.web.SecurityFilterChain;
import org.springframework.security.web.authentication.AuthenticationSuccessHandler;

/**
 * A diferencia de un login "normal" con Spring Security (una sola sesion
 * autenticada por navegador), este lab necesita sesiones independientes por
 * nube: puedes estar logueado en AWS y no en Azure/GCP a la vez.
 *
 * Por eso NO se usa el SecurityContext/[authenticated] estandar de Spring
 * para proteger rutas. En vez de eso: se permite el acceso a todo (los
 * controladores deciden manualmente si hay sesion, ver ViewController), y
 * el unico trabajo de Spring Security aqui es correr el flujo OAuth2/OIDC
 * y, al terminar, guardar la identidad en la HttpSession bajo la clave
 * "user_{cloud}" (igual que Python guarda user_aws/user_azure/user_gcp).
 */
@Configuration
public class SecurityConfig {

    @Bean
    public SecurityFilterChain filterChain(HttpSecurity http) throws Exception {
        http
            .authorizeHttpRequests(auth -> auth.anyRequest().permitAll())
            .oauth2Login(oauth2 -> oauth2.successHandler(successHandler()));
        return http.build();
    }

    private AuthenticationSuccessHandler successHandler() {
        return (request, response, authentication) -> {
            var token = (org.springframework.security.oauth2.client.authentication.OAuth2AuthenticationToken) authentication;
            String registrationId = token.getAuthorizedClientRegistrationId();
            OAuth2User user = token.getPrincipal();

            // El registrationId de Google se llama "google" en application.yml,
            // pero en las rutas de la app la nube se llama "gcp".
            String cloud = "google".equals(registrationId) ? "gcp" : registrationId;

            request.getSession().setAttribute("user_" + cloud, user.getAttributes());
            response.sendRedirect("/" + cloud + "/private");
        };
    }
}
