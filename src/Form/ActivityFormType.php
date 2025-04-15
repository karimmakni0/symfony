<?php

namespace App\Form;

use App\Entity\Activities;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Type;

class ActivityFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('activity_name', TextType::class, [
                'label' => 'Activity Name',
                'attr' => [
                    'class' => 'border-light',
                    'placeholder' => 'Enter activity name'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an activity name',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Activity name must be at least {{ limit }} characters long',
                    ]),
                ],
            ])
            ->add('activity_description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'class' => 'border-light',
                    'rows' => '5',
                    'placeholder' => 'Enter activity description'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a description',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Description must be at least {{ limit }} characters long',
                    ]),
                ],
            ])
            ->add('activity_destination', ChoiceType::class, [
                'label' => 'Destination',
                'choices' => $options['destinations_choices'],
                'required' => true,
                'placeholder' => 'Select a destination',
                'attr' => ['class' => 'border-light'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a destination',
                    ]),
                ],
            ])
            ->add('activity_duration', TextType::class, [
                'label' => 'Duration',
                'required' => true,
                'attr' => [
                    'class' => 'border-light',
                    'placeholder' => 'e.g. 2 hours, 3 days'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a duration',
                    ]),
                ],
            ])
            ->add('activity_price', MoneyType::class, [
                'label' => 'Price',
                'currency' => 'TND',
                'divisor' => 1,
                'required' => true,
                'attr' => [
                    'class' => 'border-light',
                    'placeholder' => 'Enter price in TND'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a price',
                    ]),
                    new Positive([
                        'message' => 'Price must be a positive number',
                    ]),
                ],
            ])
            ->add('activity_genre', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => [
                    'Adventure' => 'Adventure',
                    'Cultural' => 'Cultural',
                    'Relaxation' => 'Relaxation',
                    'Family' => 'Family',
                    'Romantic' => 'Romantic',
                    'Educational' => 'Educational',
                    'Sport' => 'Sport',
                    'Other' => 'Other'
                ],
                'required' => true,
                'placeholder' => 'Select genre',
                'attr' => ['class' => 'border-light'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a genre',
                    ]),
                ],
            ])
            ->add('activity_date', DateType::class, [
                'label' => 'Activity Date',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'class' => 'border-light',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select an activity date',
                    ]),
                    new GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'The activity date must be today or a future date',
                    ]),
                ],
            ])
            ->add('max_number', IntegerType::class, [
                'label' => 'Maximum Participants',
                'required' => true,
                'attr' => [
                    'class' => 'border-light',
                    'min' => 10,
                    'placeholder' => 'Enter maximum number of participants'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter maximum number of participants',
                    ]),
                    new Type([
                        'type' => 'integer',
                        'message' => 'Maximum participants must be an integer',
                    ]),
                    new GreaterThanOrEqual([
                        'value' => 10,
                        'message' => 'Maximum participants must be at least {{ compared_value }}',
                    ]),
                ],
            ])
            ->add('activity_images', FileType::class, [
                'label' => 'Activity Images',
                'mapped' => false,
                'required' => $options['images_required'],
                'multiple' => true,
                'attr' => [
                    'accept' => 'image/*',
                    'multiple' => 'multiple',
                ],
                'constraints' => $this->getImageConstraints($options['images_required']),
            ])
        ;
    }

    private function getImageConstraints(bool $required): array
    {
        $constraints = [];
        
        if ($required) {
            $constraints[] = new NotBlank([
                'message' => 'Please upload at least one image',
            ]);
            
            $constraints[] = new Count([
                'min' => 1,
                'minMessage' => 'Please upload at least one image',
            ]);
        }
        
        $constraints[] = new All([
            'constraints' => [
                new File([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                    ],
                    'mimeTypesMessage' => 'Please upload valid image files (JPEG, PNG or GIF)',
                ])
            ]
        ]);
        
        return $constraints;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activities::class,
            'images_required' => true,
            'destinations_choices' => [],
        ]);
    }
}
