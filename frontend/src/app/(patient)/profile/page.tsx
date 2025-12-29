'use client';

import { useState, useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { toast } from 'sonner';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { User, Mail, Phone, MapPin, Calendar, Save, Lock, Heart, AlertTriangle } from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Skeleton } from '@/components/ui/skeleton';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useAuthStore } from '@/lib/stores/auth';
import { authApi } from '@/lib/api/auth';
import { patientApi } from '@/lib/api/patient';
import type { PatientProfile, ApiResponse } from '@/types';

const profileSchema = z.object({
  name: z.string().min(2, 'Name is required'),
  email: z.string().email().optional().or(z.literal('')),
  date_of_birth: z.string().optional(),
  gender: z.enum(['male', 'female']).optional(),
  address: z.string().optional(),
});

const medicalInfoSchema = z.object({
  blood_type: z.string().optional(),
  allergies: z.string().optional(),
  chronic_diseases: z.string().optional(),
  emergency_contact_name: z.string().optional(),
  emergency_contact_phone: z.string().optional(),
});

const passwordSchema = z
  .object({
    current_password: z.string().min(1, 'Current password is required'),
    password: z.string().min(6, 'Password must be at least 6 characters'),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  });

type ProfileFormData = z.infer<typeof profileSchema>;
type MedicalInfoFormData = z.infer<typeof medicalInfoSchema>;
type PasswordFormData = z.infer<typeof passwordSchema>;

function MedicalInfoSkeleton() {
  return (
    <Card>
      <CardHeader>
        <Skeleton className="h-6 w-48" />
      </CardHeader>
      <CardContent className="space-y-4">
        <Skeleton className="h-10 w-full" />
        <Skeleton className="h-20 w-full" />
        <Skeleton className="h-20 w-full" />
        <Skeleton className="h-10 w-32" />
      </CardContent>
    </Card>
  );
}

export default function ProfilePage() {
  const t = useTranslations();
  const queryClient = useQueryClient();
  const { user, setUser } = useAuthStore();
  const [isUpdating, setIsUpdating] = useState(false);
  const [isChangingPassword, setIsChangingPassword] = useState(false);

  // Fetch patient profile (medical info)
  const { data: patientProfile, isLoading: isLoadingProfile } = useQuery<ApiResponse<PatientProfile>>({
    queryKey: ['patient-profile'],
    queryFn: () => patientApi.getProfile(),
  });

  const profileForm = useForm<ProfileFormData>({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      name: user?.name || '',
      email: user?.email || '',
      date_of_birth: user?.date_of_birth || '',
      gender: user?.gender || undefined,
      address: user?.address || '',
    },
  });

  const medicalForm = useForm<MedicalInfoFormData>({
    resolver: zodResolver(medicalInfoSchema),
    defaultValues: {
      blood_type: '',
      allergies: '',
      chronic_diseases: '',
      emergency_contact_name: '',
      emergency_contact_phone: '',
    },
  });

  const passwordForm = useForm<PasswordFormData>({
    resolver: zodResolver(passwordSchema),
    defaultValues: {
      current_password: '',
      password: '',
      password_confirmation: '',
    },
  });

  // Update medical form when profile data loads
  useEffect(() => {
    if (patientProfile?.data) {
      medicalForm.reset({
        blood_type: patientProfile.data.blood_type || '',
        allergies: patientProfile.data.allergies || '',
        chronic_diseases: patientProfile.data.chronic_diseases || '',
        emergency_contact_name: patientProfile.data.emergency_contact_name || '',
        emergency_contact_phone: patientProfile.data.emergency_contact_phone || '',
      });
    }
  }, [patientProfile?.data, medicalForm]);

  // Medical info mutation
  const medicalMutation = useMutation({
    mutationFn: (data: MedicalInfoFormData) => {
      // Use update if profile exists, create otherwise
      if (patientProfile?.data?.id) {
        return patientApi.updateProfile(data);
      }
      return patientApi.createProfile(data);
    },
    onSuccess: () => {
      toast.success(t('patient.profile.updateSuccess'));
      queryClient.invalidateQueries({ queryKey: ['patient-profile'] });
    },
    onError: () => {
      toast.error(t('common.error'));
    },
  });

  const onProfileSubmit = async (data: ProfileFormData) => {
    setIsUpdating(true);
    try {
      const response = await authApi.updateProfile(data);
      setUser(response.data);
      toast.success(t('patient.profile.updateSuccess'));
    } catch {
      toast.error(t('common.error'));
    } finally {
      setIsUpdating(false);
    }
  };

  const onMedicalSubmit = (data: MedicalInfoFormData) => {
    medicalMutation.mutate(data);
  };

  const onPasswordSubmit = async (data: PasswordFormData) => {
    setIsChangingPassword(true);
    try {
      await authApi.changePassword(data);
      toast.success(t('common.success'));
      passwordForm.reset();
    } catch {
      toast.error(t('common.error'));
    } finally {
      setIsChangingPassword(false);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">{t('navigation.profile')}</h1>
      </div>

      <Tabs defaultValue="profile" className="space-y-6">
        <TabsList>
          <TabsTrigger value="profile">
            {t('patient.profile.personalInfo')}
          </TabsTrigger>
          <TabsTrigger value="medical">
            {t('patient.profile.medicalInfo')}
          </TabsTrigger>
          <TabsTrigger value="password">
            {t('auth.changePassword')}
          </TabsTrigger>
        </TabsList>

        {/* Profile Tab */}
        <TabsContent value="profile">
          <Card>
            <CardHeader>
              <CardTitle>{t('patient.profile.personalInfo')}</CardTitle>
            </CardHeader>
            <CardContent>
              {/* Avatar */}
              <div className="flex items-center gap-4 mb-6">
                <Avatar className="h-20 w-20">
                  <AvatarImage src={user?.avatar || ''} />
                  <AvatarFallback className="text-2xl">
                    {user?.name?.charAt(0) || 'U'}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <p className="font-medium text-lg">{user?.name}</p>
                  <p className="text-sm text-gray-500">{user?.phone}</p>
                </div>
              </div>

              <Form {...profileForm}>
                <form
                  onSubmit={profileForm.handleSubmit(onProfileSubmit)}
                  className="space-y-4"
                >
                  <FormField
                    control={profileForm.control}
                    name="name"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>{t('auth.name')}</FormLabel>
                        <FormControl>
                          <div className="relative">
                            <User className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input className="ps-10" {...field} />
                          </div>
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={profileForm.control}
                    name="email"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>{t('auth.email')}</FormLabel>
                        <FormControl>
                          <div className="relative">
                            <Mail className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input type="email" className="ps-10" {...field} />
                          </div>
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField
                      control={profileForm.control}
                      name="date_of_birth"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>{t('patient.profile.dateOfBirth')}</FormLabel>
                          <FormControl>
                            <div className="relative">
                              <Calendar className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                              <Input type="date" className="ps-10" {...field} />
                            </div>
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <FormField
                      control={profileForm.control}
                      name="gender"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>{t('patient.profile.gender')}</FormLabel>
                          <Select
                            onValueChange={field.onChange}
                            defaultValue={field.value}
                          >
                            <FormControl>
                              <SelectTrigger>
                                <SelectValue placeholder={t('patient.profile.gender')} />
                              </SelectTrigger>
                            </FormControl>
                            <SelectContent>
                              <SelectItem value="male">
                                {t('patient.profile.male')}
                              </SelectItem>
                              <SelectItem value="female">
                                {t('patient.profile.female')}
                              </SelectItem>
                            </SelectContent>
                          </Select>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                  </div>

                  <FormField
                    control={profileForm.control}
                    name="address"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>{t('patient.profile.address')}</FormLabel>
                        <FormControl>
                          <div className="relative">
                            <MapPin className="absolute start-3 top-3 h-4 w-4 text-gray-400" />
                            <Textarea className="ps-10" rows={2} {...field} />
                          </div>
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <Button type="submit" disabled={isUpdating}>
                    {isUpdating ? (
                      <span className="flex items-center gap-2">
                        <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                        {t('common.save')}...
                      </span>
                    ) : (
                      <>
                        <Save className="h-4 w-4 me-2" />
                        {t('common.save')}
                      </>
                    )}
                  </Button>
                </form>
              </Form>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Medical Info Tab */}
        <TabsContent value="medical">
          {isLoadingProfile ? (
            <MedicalInfoSkeleton />
          ) : (
            <Card>
              <CardHeader>
                <CardTitle>{t('patient.profile.medicalInfo')}</CardTitle>
              </CardHeader>
              <CardContent>
                <Form {...medicalForm}>
                  <form
                    onSubmit={medicalForm.handleSubmit(onMedicalSubmit)}
                    className="space-y-4"
                  >
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <FormField
                        control={medicalForm.control}
                        name="blood_type"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>{t('patient.profile.bloodType')}</FormLabel>
                            <Select
                              onValueChange={field.onChange}
                              value={field.value}
                            >
                              <FormControl>
                                <SelectTrigger>
                                  <SelectValue placeholder={t('patient.profile.bloodType')} />
                                </SelectTrigger>
                              </FormControl>
                              <SelectContent>
                                <SelectItem value="A+">A+</SelectItem>
                                <SelectItem value="A-">A-</SelectItem>
                                <SelectItem value="B+">B+</SelectItem>
                                <SelectItem value="B-">B-</SelectItem>
                                <SelectItem value="AB+">AB+</SelectItem>
                                <SelectItem value="AB-">AB-</SelectItem>
                                <SelectItem value="O+">O+</SelectItem>
                                <SelectItem value="O-">O-</SelectItem>
                              </SelectContent>
                            </Select>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>

                    <FormField
                      control={medicalForm.control}
                      name="allergies"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>
                            <div className="flex items-center gap-2">
                              <AlertTriangle className="h-4 w-4 text-yellow-500" />
                              {t('patient.profile.allergies')}
                            </div>
                          </FormLabel>
                          <FormControl>
                            <Textarea
                              rows={2}
                              placeholder={t('patient.profile.allergiesPlaceholder') || ''}
                              {...field}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <FormField
                      control={medicalForm.control}
                      name="chronic_diseases"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>
                            <div className="flex items-center gap-2">
                              <Heart className="h-4 w-4 text-red-500" />
                              {t('patient.profile.chronicDiseases')}
                            </div>
                          </FormLabel>
                          <FormControl>
                            <Textarea
                              rows={2}
                              placeholder={t('patient.profile.chronicDiseasesPlaceholder') || ''}
                              {...field}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <div className="border-t pt-4 mt-4">
                      <h3 className="text-sm font-medium mb-4">{t('patient.profile.emergencyContact')}</h3>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormField
                          control={medicalForm.control}
                          name="emergency_contact_name"
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>{t('patient.profile.emergencyContactName')}</FormLabel>
                              <FormControl>
                                <div className="relative">
                                  <User className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                  <Input className="ps-10" {...field} />
                                </div>
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={medicalForm.control}
                          name="emergency_contact_phone"
                          render={({ field }) => (
                            <FormItem>
                              <FormLabel>{t('patient.profile.emergencyContactPhone')}</FormLabel>
                              <FormControl>
                                <div className="relative">
                                  <Phone className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                  <Input className="ps-10" type="tel" {...field} />
                                </div>
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                      </div>
                    </div>

                    <Button type="submit" disabled={medicalMutation.isPending}>
                      {medicalMutation.isPending ? (
                        <span className="flex items-center gap-2">
                          <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                          {t('common.save')}...
                        </span>
                      ) : (
                        <>
                          <Save className="h-4 w-4 me-2" />
                          {t('common.save')}
                        </>
                      )}
                    </Button>
                  </form>
                </Form>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* Password Tab */}
        <TabsContent value="password">
          <Card>
            <CardHeader>
              <CardTitle>{t('auth.changePassword')}</CardTitle>
            </CardHeader>
            <CardContent>
              <Form {...passwordForm}>
                <form
                  onSubmit={passwordForm.handleSubmit(onPasswordSubmit)}
                  className="space-y-4 max-w-md"
                >
                  <FormField
                    control={passwordForm.control}
                    name="current_password"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>{t('auth.currentPassword')}</FormLabel>
                        <FormControl>
                          <div className="relative">
                            <Lock className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input type="password" className="ps-10" {...field} />
                          </div>
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={passwordForm.control}
                    name="password"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>{t('auth.newPassword')}</FormLabel>
                        <FormControl>
                          <div className="relative">
                            <Lock className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input type="password" className="ps-10" {...field} />
                          </div>
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={passwordForm.control}
                    name="password_confirmation"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>{t('auth.confirmPassword')}</FormLabel>
                        <FormControl>
                          <div className="relative">
                            <Lock className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input type="password" className="ps-10" {...field} />
                          </div>
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <Button type="submit" disabled={isChangingPassword}>
                    {isChangingPassword ? (
                      <span className="flex items-center gap-2">
                        <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                        {t('auth.changePassword')}...
                      </span>
                    ) : (
                      t('auth.changePassword')
                    )}
                  </Button>
                </form>
              </Form>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
