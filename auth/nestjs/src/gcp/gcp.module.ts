import { Module } from "@nestjs/common";

import { GcpClientService } from "./gcp-client.service";
import { GcpController } from "./gcp.controller";

@Module({
  controllers: [GcpController],
  providers: [GcpClientService],
})
export class GcpModule {}
