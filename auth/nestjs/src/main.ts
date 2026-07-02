import "reflect-metadata";
import { NestFactory } from "@nestjs/core";
import session from "express-session";
import { AppModule } from "./app.module";

async function bootstrap() {
  const app = await NestFactory.create(AppModule);
  app.use(
    session({
      secret: process.env.SESSION_SECRET_KEY || "change-me",
      resave: false,
      saveUninitialized: false,
    })
  );
  await app.listen(8003);
  // eslint-disable-next-line no-console
  console.log("NestJS SSO lab listening on 8003");
}

bootstrap();
